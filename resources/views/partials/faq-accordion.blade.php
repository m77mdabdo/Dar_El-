{{--
    Expects $faqs: array of ['q' => string, 'a' => string]
--}}
<div class="dj-faq-wrap">
    @foreach ($faqs as $faq)
        <div class="dj-faq-item dj-reveal">
            <div class="dj-faq-q" onclick="djToggleFaq(this)" role="button" tabindex="0" onkeydown="if(event.key==='Enter')djToggleFaq(this)">
                <span>{{ $faq['q'] }}</span><span class="dj-plus">+</span>
            </div>
            <div class="dj-faq-a"><p>{{ $faq['a'] }}</p></div>
        </div>
    @endforeach
</div>
