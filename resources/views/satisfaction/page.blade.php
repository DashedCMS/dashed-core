@php($s = \Dashed\DashedCore\Satisfaction\Satisfaction::class)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $s::text('page-title', 'Wat vond je ervan?') }} - {{ $branding['siteName'] }}</title>
    <style>
        :root { --dsf-primary: {{ $branding['primaryColor'] }}; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #1f2937; background: {{ $branding['backgroundColor'] }}; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px; }
        .dsf-card { background: #fff; border-radius: 12px; max-width: 480px; width: 100%; padding: 32px 24px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .dsf-logo { max-height: 48px; max-width: 200px; margin-bottom: 16px; }
        .dsf-site { font-weight: 700; font-size: 18px; margin-bottom: 16px; }
        .dsf-title { font-size: 22px; margin: 0 0 20px; color: #111827; }
        .dsf-scores { display: flex; justify-content: center; gap: 8px; margin-bottom: 6px; border: 0; padding: 0; }
        .dsf-scores label { cursor: pointer; }
        .dsf-scores input { position: absolute; opacity: 0; }
        .dsf-scores span { display: inline-block; width: 44px; height: 44px; line-height: 40px; border-radius: 22px; border: 2px solid var(--dsf-primary); color: var(--dsf-primary); font-weight: 700; font-size: 17px; }
        .dsf-scores input:checked + span { background: var(--dsf-primary); color: #fff; }
        .dsf-scores input:focus-visible + span { outline: 2px solid #111827; outline-offset: 2px; }
        .dsf-scale { display: flex; justify-content: space-between; max-width: 256px; margin: 0 auto 20px; font-size: 12px; color: #9ca3af; }
        .dsf-comment { width: 100%; min-height: 100px; border: 1px solid #d1d5db; border-radius: 8px; padding: 10px; font: inherit; margin-bottom: 16px; }
        .dsf-label { display: block; text-align: left; font-size: 14px; margin-bottom: 6px; color: #4b5563; }
        .dsf-button { background: var(--dsf-primary); color: #fff; border: 0; border-radius: 8px; padding: 12px 24px; font: inherit; font-weight: 600; cursor: pointer; }
        .dsf-thanks { font-size: 17px; line-height: 1.6; color: #4b5563; margin: 0; }
    </style>
</head>
<body>
<main class="dsf-card">
    @if($branding['siteLogo'])
        <img class="dsf-logo" src="{{ $branding['siteLogo'] }}" alt="{{ $branding['siteName'] }}">
    @else
        <div class="dsf-site">{{ $branding['siteName'] }}</div>
    @endif

    @if($saved)
        <h1 class="dsf-title">{{ $s::text('thanks-title', 'Bedankt voor je reactie!') }}</h1>
        <p class="dsf-thanks">{{ $s::text('thanks-text', 'Met jouw antwoord maken we onze service beter.') }}</p>
    @else
        <h1 class="dsf-title">{{ $rating->source === $s::SOURCE_CHAT ? $s::text('question-chat', 'Hoe tevreden ben je over ons chatgesprek?') : $s::text('question-delivery', 'Hoe tevreden ben je over je bestelling?') }}</h1>
        <form method="post" action="{{ $action }}">
            @csrf
            <fieldset class="dsf-scores">
                @foreach(range(1, 5) as $option)
                    <label><input type="radio" name="score" value="{{ $option }}" @checked($option === $score)><span>{{ $option }}</span></label>
                @endforeach
            </fieldset>
            <div class="dsf-scale"><span>{{ $s::text('scale-low', 'Slecht') }}</span><span>{{ $s::text('scale-high', 'Uitstekend') }}</span></div>
            <label class="dsf-label" for="dsf-comment">{{ $s::text('comment-label', 'Wil je er nog iets over kwijt? (optioneel)') }}</label>
            <textarea class="dsf-comment" id="dsf-comment" name="comment" maxlength="2000">{{ old('comment', $rating->comment) }}</textarea>
            <button class="dsf-button" type="submit">{{ $s::text('submit', 'Verstuur') }}</button>
        </form>
    @endif
</main>
</body>
</html>
