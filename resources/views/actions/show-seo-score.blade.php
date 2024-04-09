<div>
    @if($seoScore)
        <p>De huidige SEO score is: {{ $seoScore->score }}</p>

    @else
        <p>Er is nog geen SEO score bekend, sla op om te laten berekenen</p>
    @endif
</div>
