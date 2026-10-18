
@props(['number', 'title'])

<section {{ $attributes->merge([
    'class' => 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5'
]) }}>
    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1d2d50] text-sm font-bold text-white">
            {{ $number }}
        </span>
        <h2 class="font-bold text-[#1d2d50]">{{ $title }}</h2>
    </div>

    {{ $slot }}
</section>
