@props(['icon' => 'inbox', 'title' => 'Belum ada data', 'message' => null])
<div class="empty-state"><i class="bi bi-{{ $icon }}"></i><h6 class="mt-3 mb-1">{{ $title }}</h6>@if($message)<p class="mb-0">{{ $message }}</p>@endif{{ $slot }}</div>
