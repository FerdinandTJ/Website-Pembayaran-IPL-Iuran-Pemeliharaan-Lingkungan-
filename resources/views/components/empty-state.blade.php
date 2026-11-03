@props(['icon' => 'inbox', 'title' => 'Belum ada data', 'message' => null])
<div class="empty-state"><span class="empty-state-icon"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></span><h6>{{ $title }}</h6>@if($message)<p>{{ $message }}</p>@endif{{ $slot }}</div>
