#!/usr/bin/env python3
"""Check documentation structure/traceability and an optional isolated schema export.

This checks references and contracts between artifacts, not the truth of fieldwork,
UAT results, every business statement, or the meaning of every diagram.
"""
import argparse
from collections import Counter
import json
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[2]
DOCS = ROOT / 'docs'


def require(condition, message):
    if not condition:
        raise ValueError(message)


def read(path):
    return (ROOT / path).read_text(encoding='utf-8')


def refs(text, prefix):
    found = set()
    pattern = r'\b' + re.escape(prefix) + r'-(\d+(?:[–-]\d+)?(?:[/,]\s*\d+(?:[–-]\d+)?)*)'
    for match in re.finditer(pattern, text):
        for part in re.split(r'[/,]\s*', match[1]):
            bounds = re.split('[–-]', part)
            found.update(range(int(bounds[0]), int(bounds[-1]) + 1))
    return found


def rows(text, prefix):
    matches = list(re.finditer(r'^\| ' + re.escape(prefix) + r'-(\d+) \|.*$', text, re.M))
    ids = [int(m[1]) for m in matches]
    require(len(ids) == len(set(ids)), f'Duplicate {prefix} definition rows')
    return {int(m[1]): m[0] for m in matches}


def slugs(text):
    counts = Counter()
    result = set()
    for heading in re.findall(r'^#+\s+(.+)$', text, re.M):
        base = re.sub(r'[^\w\- ]', '', heading.lower()).replace(' ', '-')
        suffix = f'-{counts[base]}' if counts[base] else ''
        counts[base] += 1
        result.add(base + suffix)
    return result


def markdown_checks(files):
    for path in files:
        text = path.read_text(encoding='utf-8')
        require(text.count('```') % 2 == 0, f'Unclosed code fence: {path.relative_to(ROOT)}')
        for target in re.findall(r'!?\[[^\]]*\]\(([^)]+)\)', text):
            if '://' in target or target.startswith('mailto:'):
                continue
            name, _, anchor = target.partition('#')
            dest = (path.parent / name).resolve() if name else path
            require(dest.exists(), f'Missing link in {path.relative_to(ROOT)}: {target}')
            if anchor and dest.suffix == '.md':
                require(anchor in slugs(dest.read_text(encoding='utf-8')),
                        f'Missing anchor in {path.relative_to(ROOT)}: {target}')
        block = []
        for line in text.splitlines() + ['']:
            if line.startswith('|'):
                block.append(line)
            else:
                if len(block) > 1 and re.match(r'^\|\s*:?-+', block[1]):
                    widths = [len(re.split(r'(?<!\\)\|', row)) for row in block]
                    require(len(set(widths)) == 1, f'Table column mismatch: {path.relative_to(ROOT)} / {block[0]}')
                block = []
        for name in set(re.findall(r'\b[A-Z]\w+Test\b', text)):
            require((ROOT / 'tests/Feature' / f'{name}.php').exists(), f'Unknown test {name}: {path}')
    test_sources = '\n'.join(p.read_text() for p in (ROOT / 'tests/Feature').glob('*.php'))
    for path in files:
        for method in re.findall(r'`(test_\w+)`', path.read_text()):
            require(f'function {method}(' in test_sources, f'Unknown test method {method}: {path}')


def trace_checks():
    req = read('docs/srs/01-KEBUTUHAN.md')
    cases = read('docs/srs/05-VERIFIKASI.md')
    use_cases = read('docs/perancangan/02-USE-CASE-DAN-AKTIVITAS.md')
    ui = read('docs/perancangan/04-ANTARMUKA-MASUKAN-KELUARAN.md')
    plan = read('docs/perancangan/05-RENCANA-PENGUJIAN.md')
    matrix = read('docs/perancangan/08-MATRIKS-KETERTELUSURAN.md')
    source = {prefix: rows(req, prefix) for prefix in ['FR', 'BR', 'NFR']}
    definitions = {'AT': rows(cases, 'AT'), 'UI': rows(ui, 'UI')}
    for prefix in ['BB', 'BV', 'DT', 'OP']:
        definitions[prefix] = rows(plan, prefix)
    sections = re.split(r'^### RUC-', use_cases, flags=re.M)[1:]
    definitions['RUC'] = {int(s.split(' ', 1)[0]): s for s in sections}
    require(len(sections) == len(definitions['RUC']), 'Duplicate RUC headings')
    for prefix in ['FR', 'BR', 'NFR']:
        require(set(rows(matrix, prefix)) == set(source[prefix]), f'{prefix} missing/extra in traceability matrix')
    for prefix, text in [('RUC', use_cases), ('UI', ui), ('AT', cases), ('BB', plan), ('BV', plan), ('DT', plan), ('OP', plan)]:
        require(set(definitions[prefix]), f'No {prefix} definitions')
    mappings = {key: {number: refs(body, 'FR') for number, body in definitions[key].items()}
                for key in ['RUC', 'UI', 'AT', 'BB']}
    for fr, line in rows(matrix, 'FR').items():
        cells = [cell.strip() for cell in line.split('|')[1:-1]]
        expected_label = source['FR'][fr].split('|')[2].strip()
        require(cells[1] == expected_label, f'FR-{fr:02} definition drift in matrix')
        for column, prefix in enumerate(['RUC', 'UI', 'AT', 'BB'], 2):
            expected = {number for number, frs in mappings[prefix].items() if fr in frs}
            require(expected and refs(cells[column], prefix) == expected, f'FR-{fr:02} / {prefix} matrix mismatch')
    for path in [DOCS / 'BAB-III-PERANCANGAN.md', *sorted((DOCS / 'perancangan').glob('*.md'))]:
        text = path.read_text()
        for prefix, available in {**source, **definitions}.items():
            require(refs(text, prefix) <= set(available), f'Undefined {prefix} reference in {path.name}')
    stats = {key: len(value) for key, value in {**source, **definitions}.items()}
    return stats


def schema_checks(path):
    schema = json.loads(path.read_text())
    schema = {key.removeprefix('main.'): value for key, value in schema.items()}
    text = read('docs/srs/03-PDM-KAMUS-DATA.md')
    require(set(re.findall(r'^#### `([^`]+)`$', text, re.M)) == set(schema), 'PDM table set differs from schema')
    totals = {'tables': len(schema), 'columns': 0, 'foreign_keys': 0, 'indexes': 0}
    for table, data in schema.items():
        section = text.split(f'#### `{table}`\n', 1)[1].split('\n#### ', 1)[0].split('\n### ', 1)[0]
        indices = list(data['indexes'].values()) if isinstance(data['indexes'], dict) else data['indexes']
        fks = list(data['foreign_keys'].values()) if isinstance(data['foreign_keys'], dict) else data['foreign_keys']
        columns = data['columns']
        actual = re.findall(r'^\| `([^`]+)` \| `([^`]+)` \| (Ya|Tidak) \| ([^|]+) \| ([^|]+) \|$', section, re.M)
        require(len(actual) == len(columns), f'{table}: column count mismatch')
        for row, col in zip(actual, columns):
            require(row[0] == col['name'] and row[1] == col['type'] and (row[2] == 'Ya') == col['nullable'],
                    f'{table}.{col["name"]}: name/type/nullability mismatch')
            default = '—' if col['default'] is None else '`' + str(col['default']) + '`'
            require(row[3].strip() == default, f'{table}.{col["name"]}: default mismatch')
            flags = {part.strip() for part in row[4].split(',')} & {'PK', 'UQ', 'FK', 'AI'}
            expected = {key for key, yes in [
                ('PK', any(i['primary'] and col['name'] in i['columns'] for i in indices)),
                ('UQ', any(i['unique'] and not i['primary'] and i['columns'] == [col['name']] for i in indices)),
                ('FK', any(col['name'] in f['columns'] for f in fks)), ('AI', col['auto_increment'])] if yes}
            require(flags == expected, f'{table}.{col["name"]}: key flags mismatch')
        for index in indices:
            kind = 'PK' if index['primary'] else 'UQ' if index['unique'] else 'INDEX'
            label = f"`{index['name']}` — {kind} (`{'`, `'.join(index['columns'])}`)"
            require(label in section, f'{table}: index mismatch {index["name"]}')
        for fk in fks:
            label = f"(`{'`, `'.join(fk['columns'])}`) → `{fk['foreign_table']}` (`{'`, `'.join(fk['foreign_columns'])}`); ON DELETE **{fk['on_delete'].upper()}**, ON UPDATE **{fk['on_update'].upper()}**"
            require(label in section, f'{table}: FK action/target mismatch')
        totals['columns'] += len(columns)
        totals['foreign_keys'] += len(fks)
        totals['indexes'] += len(indices)
    label = f"{totals['tables']} tabel, {totals['columns']} kolom, {totals['foreign_keys']} foreign key dan {totals['indexes']} indeks"
    require(label in text, 'PDM summary totals differ from schema')
    return totals


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--schema', type=Path, help='JSON exported by docs/tools/export_schema.php')
    parser.add_argument('--mermaid-cli', help='Optional path to mmdc; executes rendering, not only fence checks')
    parser.add_argument('--puppeteer-config', help='Optional renderer browser configuration')
    args = parser.parse_args()
    files = [ROOT / 'README.md', ROOT / 'SECURITY.md', *sorted(DOCS.rglob('*.md'))]
    markdown_checks(files)
    stats = trace_checks()
    figures = sorted((DOCS / 'images/perancangan').glob('*.svg'))
    for path in figures:
        root = ET.parse(path).getroot()
        require(root.attrib.get('viewBox') and root.find('{http://www.w3.org/2000/svg}title') is not None,
                f'SVG lacks viewBox/title: {path.name}')
        require(not any(element.tag.split('}')[-1] == 'script' for element in root.iter()), f'Script in SVG: {path}')
        require(any(path.name in doc.read_text() for doc in files), f'SVG not referenced by docs: {path.name}')
    diagrams = {p: len(re.findall(r'^```mermaid$', p.read_text(), re.M)) for p in files}
    if args.mermaid_cli:
        with tempfile.TemporaryDirectory(prefix='enviropay-docs-render-') as output:
            for i, (path, count) in enumerate(diagrams.items()):
                if not count:
                    continue
                destination = Path(output) / f'document-{i}'
                destination.mkdir()
                command = [args.mermaid_cli, '-i', str(path), '-o', str(destination / path.name), '-b', 'white', '-j', '1']
                if args.puppeteer_config:
                    command += ['-p', args.puppeteer_config]
                result = subprocess.run(command, capture_output=True, text=True, timeout=120, cwd=ROOT)
                require(result.returncode == 0, f'Mermaid render failed: {path.name}\n{result.stdout}\n{result.stderr}')
                require(len(list(destination.glob('*.svg'))) == count, f'Missing rendered diagrams: {path.name}')
                print(f'Rendered {path.relative_to(ROOT)} ({count})', flush=True)
    result = {'traceability': stats, 'svg_figures': len(figures), 'mermaid_blocks': sum(diagrams.values()),
              'render_executed': bool(args.mermaid_cli)}
    if args.schema:
        result['schema'] = schema_checks(args.schema)
    print(json.dumps(result, ensure_ascii=False, indent=2))
    print('PASS — structure, references and artifact mappings verified; fieldwork/UAT/semantic review are not automated.')


if __name__ == '__main__':
    try:
        main()
    except (ValueError, OSError, subprocess.TimeoutExpired, ET.ParseError) as error:
        print(f'FAIL: {error}', file=sys.stderr)
        sys.exit(1)
