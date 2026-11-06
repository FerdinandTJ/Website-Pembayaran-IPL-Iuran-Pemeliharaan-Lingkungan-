<style>
    :root{color-scheme:light;--green:#176b52;--ink:#223b33;--muted:#63766a;--border:#e3eae3}
    *{box-sizing:border-box}
    body{margin:0;background:#f5f7f4;color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;-webkit-font-smoothing:antialiased}
    a{color:inherit;text-decoration:none}
    a:focus-visible{outline:3px solid #81bba9;outline-offset:5px}
    .error-shell{max-width:1120px;min-height:100vh;min-height:100svh;margin:auto;padding:36px 32px 24px;display:flex;flex-direction:column;justify-content:center}
    .error-header,.error-footer{display:flex;align-items:center;justify-content:space-between;gap:20px}
    .error-header{margin-bottom:38px}
    .brand{display:inline-flex;align-items:center;gap:11px;font-size:22px;font-weight:750;letter-spacing:-.8px}
    .brand-icon{display:grid;place-items:center;width:43px;height:43px;background:#176b52;border-radius:13px;color:#e0efc9}
    .brand-icon svg{width:28px;height:28px}
    .brand small{display:block;margin-top:3px;color:var(--muted);font-size:11px;font-weight:450;letter-spacing:.05px}
    .header-label{color:var(--muted);font-size:12px}
    .error-card{display:grid;grid-template-columns:1fr 1fr;background:#fff;border:1px solid var(--border);border-radius:24px;overflow:hidden;box-shadow:0 20px 70px #234d3210}
    .error-art{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:space-between;min-height:490px;padding:32px 24px;background:#edf3e8;overflow:hidden}
    .error-art:before,.error-art:after{content:"";position:absolute;pointer-events:none;border:1px solid #dbe6d3;border-radius:50%;width:390px;height:390px;left:-255px;top:115px}
    .error-art:after{width:300px;height:300px;top:-185px;left:auto;right:-100px}
    .art-top{display:flex;align-items:center;gap:8px;font-size:9px;font-weight:600;letter-spacing:1.2px;color:#5e7b62;text-align:center;z-index:1}
    .small-dot{display:inline-block;width:6px;height:6px;border-radius:50%;background:#759b67;flex-shrink:0}
    .scene{width:100%;max-width:440px;position:relative;padding-top:62px;margin-top:12px}
    .error-code{position:absolute;top:-25px;width:100%;text-align:center;color:#ccddc0;font-size:clamp(110px,13vw,164px);font-weight:800;letter-spacing:-10px;line-height:1}
    .neighborhood{position:relative;display:block;width:100%;height:auto}
    .art-caption{margin:0;text-align:center;line-height:1.7;color:#6a8269;font-size:13px;letter-spacing:-.1px;z-index:1}
    .art-caption strong{font-weight:600;color:#41634c}
    .error-copy{display:flex;flex-direction:column;justify-content:center;align-items:flex-start;padding:48px 44px}
    .status-pill{display:inline-flex;align-items:center;gap:8px;padding:8px 11px;border:1px solid #e0eadd;border-radius:7px;background:#f6f9f3;color:#56754f;font-size:10px;font-weight:650;letter-spacing:1px}
    h1{margin:22px 0 15px;font-size:clamp(27px,3vw,35px);font-weight:650;line-height:1.2;letter-spacing:-1.4px;overflow-wrap:break-word}
    .description{margin:0;color:var(--muted);font-size:14px;line-height:1.85}
    .primary-action{display:inline-flex;justify-content:center;align-items:center;gap:20px;min-height:46px;background:var(--green);color:#fff;border:1px solid var(--green);border-radius:9px;padding:13px 18px;margin-top:28px;font-size:13px;font-weight:600;transition:background .15s}
    .primary-action:hover{background:#10513e}
    .primary-action svg{width:18px;height:18px;flex-shrink:0}
    .help-note{display:flex;align-items:flex-start;gap:10px;border-top:1px solid #edf0ea;margin-top:28px;padding-top:20px;color:#70816f}
    .help-note svg{width:17px;height:17px;flex-shrink:0;margin-top:2px}
    .help-note p{margin:0;font-size:11px;line-height:1.8}
    .error-footer{margin-top:25px;color:#7a897b;font-size:10px;line-height:1.6}
    @media(max-width:700px){.error-shell{padding:24px 18px;justify-content:flex-start}.error-header{margin-bottom:24px}.header-label{display:none}.error-card{grid-template-columns:1fr;border-radius:20px}.error-art{min-height:0;padding:22px 20px 20px}.scene{max-width:265px;padding-top:38px;margin-top:30px}.error-code{font-size:112px;top:-28px;letter-spacing:-7px}.art-top{font-size:8px;letter-spacing:.9px}.art-caption{display:none}.error-copy{padding:28px 26px 30px}h1{font-size:29px;margin-top:19px}.description{font-size:13px}.primary-action{width:100%;margin-top:23px}.help-note{margin-top:22px;padding-top:17px}.error-footer{align-items:flex-start;font-size:9px;gap:14px}.error-footer span:last-child{max-width:135px;text-align:right}}
    @media(prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
