@props(['user', 'size' => 'h-10 w-10', 'class' => ''])

@php
    $nameParts = preg_split('/\s+/', trim((string) ($user?->name ?? 'Pengguna')));
    $initials = collect($nameParts)
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->class([$size, 'relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#1d2d50] text-sm font-bold text-white', $class]) }}>
    <span aria-hidden="true">{{ $initials !== '' ? $initials : 'P' }}</span>
    @if ($user?->photo)
        <img src="{{ route('user.profile.photo') }}" alt="Foto profil {{ $user->name }}"
             class="absolute inset-0 h-full w-full object-cover"
             onerror="this.remove()">
    @endif
</span>
