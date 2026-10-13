<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * YMO content batch — verbatim copy for cost pages and neighbourhood pages.
 * Do not paraphrase. [VERIFY] markers must be confirmed before publish.
 */
return array(
    'blog' => array(
        'blog/car-service-cost-pune' => array(
            'title'            => 'Car Service Cost in Pune (2026) — Doorstep Prices | YMO',
            'meta_title'       => 'Car Service Cost in Pune (2026) | YMO',
            'meta_description' => 'What does car servicing actually cost in Pune? A clear breakdown of periodic and comprehensive service prices, what affects them, and how doorstep pickup compares.',
            'h1'               => 'Car Service Cost in Pune (2026) — Doorstep Prices',
            'intro'            => 'If you\'re trying to work out what car servicing should cost in Pune, the honest answer is: it depends on your car, the type of service, and where you get it done — and most workshops won\'t tell you the range up front. Here\'s a straightforward breakdown so you can budget before you book.',
            'city_slug'        => 'pune',
            'faq'              => array(
                array(
                    'q' => 'Is doorstep car service more expensive in Pune?',
                    'a' => 'No — YMO\'s pricing is comparable to a workshop, and pick-up and drop is free, so you usually save on time and travel.',
                ),
                array(
                    'q' => 'Are parts included in the service cost?',
                    'a' => 'Standard consumables like engine oil and the oil filter are included in periodic service packages. Wear-and-tear parts are quoted separately and only replaced with your approval.',
                ),
                array(
                    'q' => 'How long does a car service take in Pune?',
                    'a' => 'A basic periodic service is usually completed the same day. A major interval service or extra repairs may take longer depending on what is found during inspection.',
                ),
                array(
                    'q' => 'How much does Maruti or Hyundai servicing cost in Pune?',
                    'a' => 'Popular hatchbacks typically start at the hatchback rates in the table below. Exact totals depend on oil grade, filters, and any parts that need replacement — we share a quote before work starts.',
                ),
            ),
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">If you're trying to work out what car servicing should cost in Pune, the honest answer is: it depends on your car, the type of service, and where you get it done — and most workshops won't tell you the range up front. Here's a straightforward breakdown so you can budget before you book.</p>
<p class="md-body-sm ymo-muted mb-4">Indicative YMO workshop prices for Pune. Wear parts and major-interval packages are quoted after inspection. Last updated October 2026.</p>

<h2 class="md-headline-md mt-4 mb-3">Price table by car segment (Pune)</h2>
<p class="md-body-md mb-3">Use this table to budget by body type. Luxury and complex jobs (clutch, suspension, etc.) need an inspection quote — see our <a href="/pricing">pricing guide</a> and individual service pages.</p>
<div class="table-responsive mb-3">
<table class="table md-body-md">
<thead><tr><th>Service</th><th>Hatchback</th><th>Sedan</th><th>SUV / MUV</th><th>Luxury</th></tr></thead>
<tbody>
<tr><td>Complete / periodic car servicing</td><td>₹2,499</td><td>₹2,499</td><td>₹2,999</td><td>₹24,999</td></tr>
<tr><td>Oil change</td><td>From ₹1,499</td><td>From ₹1,499</td><td>From ₹2,499</td><td>From ₹5,600</td></tr>
<tr><td>AC service</td><td>From ₹999</td><td>From ₹1,299</td><td>From ₹1,599</td><td>From ₹9,999</td></tr>
<tr><td>Brake service</td><td>From ₹499</td><td>From ₹499</td><td>From ₹899</td><td>From ₹2,999</td></tr>
<tr><td>Denting (per panel)</td><td>From ₹4,500</td><td>From ₹4,500</td><td>From ₹5,500</td><td>From ₹6,999</td></tr>
<tr><td>Interior deep cleaning</td><td>₹2,500</td><td>₹2,999</td><td>₹3,499</td><td>From ₹3,999</td></tr>
<tr><td>Rubbing &amp; polishing</td><td>₹6,500</td><td>₹6,500</td><td>₹7,999</td><td>From ₹9,999</td></tr>
<tr><td>Major / high-km service package</td><td colspan="4">Major / high-km interval service (e.g. 40,000 km): from ₹4,999 for hatchback — deeper checks and interval items beyond routine periodic service.</td></tr>
</tbody>
</table>
</div>
<p class="md-body-md mb-3">Battery, clutch, suspension, tyres, and insurance-assistance jobs are <strong>quote after inspection</strong> because parts and labour vary widely by model.</p>

<h2 class="md-headline-md mt-4 mb-3">What's included in each package</h2>
<p class="md-body-md mb-3"><strong>Periodic / complete car servicing</strong> (from ₹2,499 for hatchback and sedan in Pune) typically covers engine oil and filter change, brake cleaning, AC filter service, coolant top-up where needed, interior vacuum, and a general health check — with free doorstep pick-up and drop. See the full checklist on <a href="/services/complete-car-servicing">complete car servicing in Pune</a>.</p>
<p class="md-body-md mb-3"><strong>Oil change</strong> is a faster visit focused on oil, filter, and basic under-bonnet checks — ideal between full services. Book via <a href="/services/car-oil-change">car oil change in Pune</a>.</p>
<p class="md-body-md mb-3"><strong>AC, brakes, denting, and detailing</strong> are priced by segment in the table; inclusions are listed on each service page. Anything beyond standard consumables is approved by you on WhatsApp before it is fitted.</p>

<h2 class="md-headline-md mt-4 mb-3">What affects the price</h2>
<p class="md-body-md mb-3">The main drivers are your car's make and engine size (a premium sedan needs more oil and pricier filters than a small hatch), the service interval you're due for (a 40,000 km service is more involved than a routine oil change), and whether any wear items need replacing. We use OEM, OES, or brand-approved parts matched to your car; alternatives are explained on your estimate before fitment.</p>

<h2 class="md-headline-md mt-4 mb-3">YMO vs authorised service centre (Pune)</h2>
<div class="table-responsive mb-3">
<table class="table md-body-md">
<thead><tr><th></th><th>YMO (pick-up + workshop)</th><th>Typical authorised centre</th></tr></thead>
<tbody>
<tr><td>Doorstep pick-up &amp; drop</td><td>Free across Pune service areas</td><td>Usually you drive in and wait</td></tr>
<tr><td>Quote before extra work</td><td>Yes — approval on WhatsApp</td><td>Varies; add-ons sometimes appear on final bill</td></tr>
<tr><td>Workshop equipment</td><td>Full workshop for AC, body, brakes</td><td>Full workshop</td></tr>
<tr><td>Periodic service (hatchback)</td><td>From ₹2,499 (see table)</td><td>Indicative authorised-dealer periodic service for popular hatchbacks often falls in the ₹4,500–₹8,000+ range depending on brand.</td></tr>
<tr><td>Parts policy</td><td>OEM, OES, or brand-approved parts; customer-supplied parts on request where safe.</td><td>OEM parts; often higher labour rates</td></tr>
</tbody>
</table>
</div>
<p class="md-body-md mb-3">Authorised centres make sense when a warranty claim requires dealer records. For routine maintenance and repairs, many Pune owners prefer YMO when they want pick-up, transparent quotes, and workshop-grade work without losing a day at the service lane.</p>

<h2 class="md-headline-md mt-4 mb-3">Why doorstep servicing in Pune is often better value</h2>
<p class="md-body-md mb-3">With Your Mechanic Online, pick-up and drop across Pune is free, so you're not paying for your time or fuel to reach a workshop and wait. You see progress updates and approve any extra parts before they're fitted — no surprise additions to the bill.</p>

<div class="md-card-outlined p-4 my-4 ymo-author-box">
<h2 class="md-title-md mb-2">About this guide</h2>
<p class="md-body-md mb-0">Draft — YMO service operations team, last reviewed October 2026.</p>
</div>

<p class="md-body-md mt-3 mb-0"><a href="/locations/pune">Car servicing in Pune</a> · <a href="/services/complete-car-servicing">Complete car servicing</a> · <a href="/services/car-air-conditioner-servicing-in-pune">AC servicing in Pune</a> · <a href="/blog/car-service-cost-indore">Car service cost in Indore</a> · <a href="/blog/car-service-cost-nashik">Car service cost in Nashik</a></p>
</div>
<!-- ymo-cost-guide-expanded -->
HTML,
        ),
        'blog/car-service-cost-indore' => array(
            'title'            => 'Car Service Cost in Indore (2026) — Doorstep Prices | YMO',
            'meta_description' => 'A clear breakdown of what car servicing costs in Indore — periodic and comprehensive service prices, what affects them, and how free doorstep pickup compares.',
            'h1'               => 'Car Service Cost in Indore (2026) — Doorstep Prices',
            'intro'            => 'Wondering what car servicing costs in Indore before you book? Prices vary by car and service type, and most garages won\'t give you a range up front. Here\'s a clear breakdown so there are no surprises.',
            'city_slug'        => 'indore',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Wondering what car servicing costs in Indore before you book? Prices vary by car and service type, and most garages won't give you a range up front. Here's a clear breakdown so there are no surprises.</p>
<h2 class="md-headline-md mt-4 mb-3">Typical car service costs in Indore</h2>
<ul class="md-body-md mb-3">
<li class="mb-2">Basic / periodic service (oil, filter, general check): from ₹2,499</li>
<li class="mb-2">Comprehensive / complete service (deeper inspection, more parts): from ₹4,999</li>
<li class="mb-0">Small hatchbacks are cheapest to service; sedans and SUVs cost more because of larger oil quantities and part sizes.</li>
</ul>
<p class="md-body-md mb-3">These cover labour and standard consumables. Replacement parts like brake pads or a battery are additional and only fitted when genuinely needed — and always after you approve them.</p>
<h2 class="md-headline-md mt-4 mb-3">What affects the price</h2>
<p class="md-body-md mb-3">Your car's make and engine size matter most, followed by which service interval you're due for and whether any wear items need replacing. Choosing genuine over aftermarket parts also changes the total.</p>
<h2 class="md-headline-md mt-4 mb-3">Why doorstep servicing works well in Indore</h2>
<p class="md-body-md mb-3">Your Mechanic Online picks up and drops your car anywhere in Indore free of charge, so you skip the trip to a garage and the wait. You approve any extra parts before they're fitted, so the price you're quoted is the price you pay. For a busy day across Vijay Nagar, Palasia, AB Road and the wider city, that convenience is the difference between servicing your car this week and putting it off.</p>
<h2 class="md-headline-md mt-4 mb-3">FAQs</h2>
<p class="md-body-md mb-3"><em>Is doorstep car service costlier in Indore?</em> No — pricing is in line with local workshops, and free pickup/drop means you save on time and fuel.</p>
<p class="md-body-md mb-3"><em>What's included in the service price?</em> Engine oil and the oil filter are included in a standard service. Wear parts are quoted separately and only replaced with your go-ahead.</p>
<p class="md-body-md mb-3"><em>How soon can I get my car serviced?</em> Most basic services are completed the same day, subject to slot availability.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/indore">Car servicing in Indore</a> · <a href="/services/complete-car-servicing-in-indore">Complete car servicing in Indore</a> · <a href="/services/car-air-conditioner-servicing-in-indore">AC servicing in Indore</a> · <a href="/blog/car-service-cost-pune">Car service cost in Pune</a> · <a href="/blog/car-service-cost-nashik">Car service cost in Nashik</a></p>
</div>
HTML,
        ),
        'blog/car-service-cost-nashik' => array(
            'title'            => 'Car Service Cost in Nashik (2026) — Doorstep Prices | YMO',
            'meta_description' => 'What car servicing costs in Nashik — periodic and comprehensive prices, the factors that change them, and how YMO\'s free doorstep pickup compares.',
            'h1'               => 'Car Service Cost in Nashik (2026) — Doorstep Prices',
            'intro'            => 'If you want to know what car servicing costs in Nashik before booking, here\'s a clear, no-jargon breakdown. Prices depend on your car and the service type, so treat these as ranges to budget around.',
            'city_slug'        => 'nashik',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">If you want to know what car servicing costs in Nashik before booking, here's a clear, no-jargon breakdown. Prices depend on your car and the service type, so treat these as ranges to budget around.</p>
<h2 class="md-headline-md mt-4 mb-3">Typical car service costs in Nashik</h2>
<ul class="md-body-md mb-3">
<li class="mb-2">Basic / periodic service (oil, filter, general check): from ₹2,499</li>
<li class="mb-2">Comprehensive / complete service (deeper inspection, more parts): from ₹4,999</li>
<li class="mb-0">Hatchbacks are the most affordable to service; sedans and SUVs cost more due to larger oil and part requirements.</li>
</ul>
<p class="md-body-md mb-3">These figures cover labour and standard consumables. Any replacement parts are quoted separately and fitted only when needed, with your approval.</p>
<h2 class="md-headline-md mt-4 mb-3">What affects the price</h2>
<p class="md-body-md mb-3">The biggest factors are your car's make and engine size, the service interval you're due, and whether wear-and-tear items need changing. Genuine vs aftermarket parts also move the total up or down.</p>
<h2 class="md-headline-md mt-4 mb-3">Why doorstep servicing suits Nashik</h2>
<p class="md-body-md mb-3">Your Mechanic Online offers free pickup and drop across Nashik — from Gangapur Road and College Road through to Nashik Road and CIDCO — so you don't lose half a day at a garage. You watch the work and approve extras before they're done, so the quote is the final bill. No hidden charges, which for many Nashik car owners is the main reason to switch from a traditional workshop.</p>
<h2 class="md-headline-md mt-4 mb-3">FAQs</h2>
<p class="md-body-md mb-3"><em>Does doorstep service cost more in Nashik?</em> No — it's priced like a local workshop, with free pickup and drop on top.</p>
<p class="md-body-md mb-3"><em>Are parts part of the service cost?</em> Standard oil and filter are included; wear parts are quoted and replaced only with your approval.</p>
<p class="md-body-md mb-3"><em>How quickly can I book?</em> Same-day service is usually possible depending on slots.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/nashik">Car servicing in Nashik</a> · <a href="/services/complete-car-servicing-in-nashik">Complete car servicing in Nashik</a> · <a href="/services/car-air-conditioner-servicing-in-nashik">AC servicing in Nashik</a> · <a href="/blog/car-service-cost-pune">Car service cost in Pune</a> · <a href="/blog/car-service-cost-indore">Car service cost in Indore</a></p>
</div>
HTML,
        ),
        'blog/car-ac-service-cost-pune' => array(
            'title'            => 'Car AC Service & Gas Recharge Cost in Pune (2026) | YMO',
            'meta_description' => 'What car AC servicing and gas recharge cost in Pune — price ranges, what\'s included, signs your AC needs attention, and free doorstep service.',
            'h1'               => 'Car AC Service & Gas Recharge Cost in Pune (2026)',
            'intro'            => 'Car AC not cooling like it used to? Before you book a service in Pune, here\'s what it typically costs and what you\'re actually paying for.',
            'city_slug'        => 'pune',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Car AC not cooling like it used to? Before you book a service in Pune, here's what it typically costs and what you're actually paying for.</p>
<h2 class="md-headline-md mt-4 mb-3">Typical car AC service costs in Pune</h2>
<ul class="md-body-md mb-3">
<li class="mb-2">AC gas top-up / recharge: ₹1,500–₹2,500</li>
<li class="mb-2">Full AC service (gas, filter clean, performance check): ₹2,500–₹4,000</li>
<li class="mb-0">AC repair (compressor, cooling coil, or leak fixes): quoted after diagnosis, as it depends on the fault.</li>
</ul>
<p class="md-body-md mb-3">Pune's summers make a working AC essential, and a gas recharge is the most common job — but if cooling drops again quickly, the real issue is usually a leak, not just low gas, which is why a proper check beats a blind top-up.</p>
<h2 class="md-headline-md mt-4 mb-3">Signs your car AC needs servicing</h2>
<p class="md-body-md mb-3">Weak or warm airflow, a musty smell when you switch it on, unusual noise from the AC, or cooling that fades on longer drives. Any of these is worth a diagnosis before it becomes a bigger repair.</p>
<h2 class="md-headline-md mt-4 mb-3">What affects the cost</h2>
<p class="md-body-md mb-3">The refrigerant type and quantity your car takes, whether it's a simple recharge or a leak repair, and whether the cabin filter or cooling coil needs cleaning or replacing. A diagnosis first means you only pay for what's actually wrong.</p>
<h2 class="md-headline-md mt-4 mb-3">Why doorstep AC service in Pune makes sense</h2>
<p class="md-body-md mb-3">Your Mechanic Online comes to you anywhere in Pune, free of pickup charge, diagnoses the AC on the spot, and quotes before any work. You're not paying for a blind gas refill that lasts two weeks — you get the actual cause fixed.</p>
<h2 class="md-headline-md mt-4 mb-3">FAQs</h2>
<p class="md-body-md mb-3"><em>How often should I recharge my car AC?</em> Only when cooling genuinely drops — a well-sealed system holds gas for years, so frequent recharges usually signal a leak.</p>
<p class="md-body-md mb-3"><em>Is gas recharge enough to fix weak cooling?</em> Sometimes, but if cooling fades fast afterwards, there's likely a leak that needs repair.</p>
<p class="md-body-md mb-3"><em>Do you service AC at my home or office?</em> Yes — YMO diagnoses and services the AC at your doorstep across Pune.</p>
<p class="md-body-md mt-3 mb-0"><a href="/services/car-air-conditioner-servicing-in-pune">AC servicing in Pune</a> · <a href="/locations/pune">Car servicing in Pune</a> · <a href="/blog/car-service-cost-pune">Car service cost in Pune</a></p>
</div>
HTML,
        ),
        'blog/car-denting-painting-cost-indore' => array(
            'title'            => 'Car Denting & Painting Cost in Indore (2026) | YMO',
            'meta_description' => 'What car denting and painting costs in Indore — per-panel and full-body price ranges, what affects the quote, and free doorstep pickup and drop.',
            'h1'               => 'Car Denting & Painting Cost in Indore (2026)',
            'intro'            => 'Got a dent or scratch and want to know what it\'ll cost to fix in Indore? Denting and painting is priced by the job, so here\'s how to think about it before you book.',
            'city_slug'        => 'indore',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Got a dent or scratch and want to know what it'll cost to fix in Indore? Denting and painting is priced by the job, so here's how to think about it before you book.</p>
<h2 class="md-headline-md mt-4 mb-3">Typical denting &amp; painting costs in Indore</h2>
<ul class="md-body-md mb-3">
<li class="mb-2">Single panel (one door or fender): from ₹4,500 per panel</li>
<li class="mb-2">Multiple panels / larger area: ₹4,500 per panel (quoted for each affected area)</li>
<li class="mb-0">Full-body repaint: from ₹40,000, depending on car size and paint type.</li>
</ul>
<p class="md-body-md mb-3">The variation is real because a small door ding is a very different job from a repaint that has to colour-match across panels.</p>
<h2 class="md-headline-md mt-4 mb-3">What affects the cost</h2>
<p class="md-body-md mb-3">The size and depth of the damage, how many panels are involved, your car's paint type and colour (metallic and pearl finishes cost more to match), and whether it's a simple dent pull or needs filling and repainting. Original-quality paint and proper colour-matching cost more but last far longer than a cheap touch-up.</p>
<h2 class="md-headline-md mt-4 mb-3">Why doorstep denting &amp; painting in Indore is convenient</h2>
<p class="md-body-md mb-3">Your Mechanic Online assesses the damage, gives you a clear quote, and handles pickup and drop across Indore free of charge. You approve the quote before work starts — no vague "we'll see" pricing. For anyone in Vijay Nagar, Palasia or the wider city, it means fixing a dent without losing a day at a body shop.</p>
<h2 class="md-headline-md mt-4 mb-3">FAQs</h2>
<p class="md-body-md mb-3"><em>Can a small dent be fixed without full repainting?</em> Often yes — minor dents can be pulled or treated without a repaint, which keeps the cost down. A proper assessment tells you which applies.</p>
<p class="md-body-md mb-3"><em>Will the new paint match my car's colour?</em> Yes — colour is matched to your car's exact shade, including metallic finishes.</p>
<p class="md-body-md mb-3"><em>Do you pick up and drop the car in Indore?</em> Yes — free pickup and drop anywhere in Indore.</p>
<p class="md-body-md mt-3 mb-0"><a href="/services/car-denting-and-painting-in-indore-3000">Denting in Indore</a> · <a href="/locations/indore">Car servicing in Indore</a> · <a href="/blog/car-denting-painting-cost-pune">Denting cost guide for Pune</a></p>
</div>
HTML,
        ),
        'blog/car-denting-painting-cost-nashik' => array(
            'title'            => 'Car Denting & Painting Cost in Nashik (2026) | YMO',
            'meta_description' => 'What car denting and painting costs in Nashik — per-panel and full-body price ranges, what changes the quote, and free doorstep pickup and drop.',
            'h1'               => 'Car Denting & Painting Cost in Nashik (2026)',
            'intro'            => 'Need a dent or scratch fixed in Nashik and want a sense of the cost first? Denting and painting is quoted per job, so here\'s a clear guide before you book.',
            'city_slug'        => 'nashik',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Need a dent or scratch fixed in Nashik and want a sense of the cost first? Denting and painting is quoted per job, so here's a clear guide before you book.</p>
<h2 class="md-headline-md mt-4 mb-3">Typical denting &amp; painting costs in Nashik</h2>
<ul class="md-body-md mb-3">
<li class="mb-2">Single panel (one door or fender): from ₹4,500 per panel</li>
<li class="mb-2">Multiple panels / larger area: ₹4,500 per panel (quoted for each affected area)</li>
<li class="mb-0">Full-body repaint: from ₹40,000, depending on car size and paint type.</li>
</ul>
<p class="md-body-md mb-3">A single scratch and a full repaint are very different jobs, which is why the range is wide — a proper assessment narrows it to your exact case.</p>
<h2 class="md-headline-md mt-4 mb-3">What affects the cost</h2>
<p class="md-body-md mb-3">The size and depth of the damage, the number of panels, your car's paint type and colour (metallic and pearl shades are harder to match), and whether the panel needs only a dent pull or full filling and repainting. Quality paint with correct colour-matching costs more but lasts much longer than a cheap fix.</p>
<h2 class="md-headline-md mt-4 mb-3">Why doorstep denting &amp; painting in Nashik is convenient</h2>
<p class="md-body-md mb-3">Your Mechanic Online inspects the damage, gives a clear upfront quote, and offers free pickup and drop across Nashik — from Gangapur Road to Nashik Road and CIDCO. You approve the quote before any work begins, so there are no surprises on the final bill.</p>
<h2 class="md-headline-md mt-4 mb-3">FAQs</h2>
<p class="md-body-md mb-3"><em>Does a small dent need a full repaint?</em> Not always — many minor dents can be fixed without repainting, keeping the cost low. An assessment confirms what's needed.</p>
<p class="md-body-md mb-3"><em>Will the paint match my car exactly?</em> Yes — the colour is matched to your car's specific shade, metallic finishes included.</p>
<p class="md-body-md mb-3"><em>Do you collect and return the car in Nashik?</em> Yes — free pickup and drop across Nashik.</p>
<p class="md-body-md mt-3 mb-0"><a href="/services/car-denting-and-painting-in-nashik-3000">Denting in Nashik</a> · <a href="/locations/nashik">Car servicing in Nashik</a> · <a href="/blog/car-denting-painting-cost-pune">Denting cost guide for Pune</a></p>
</div>
HTML,
        ),
    ),
    'localities' => array(
        'car-servicing-in-rau-indore' => array(
            'title'            => 'Car Servicing in Rau, Indore — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing in Rau, Indore. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in Rau, Indore',
            'intro'            => 'Looking for reliable car servicing in Rau? Your Mechanic Online brings the workshop to your doorstep. Rau sits on the south-western edge of Indore along the AB Road / Mhow stretch, close to the Rau–Pithampur industrial belt, so a quick trip to a city-centre garage isn\'t always practical — which is exactly where doorstep service helps.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Looking for reliable car servicing in Rau? Your Mechanic Online brings the workshop to your doorstep. Rau sits on the south-western edge of Indore along the AB Road / Mhow stretch, close to the Rau–Pithampur industrial belt, so a quick trip to a city-centre garage isn't always practical — which is exactly where doorstep service helps.</p>
<p class="md-body-md mb-3">We pick up your car from your home or workplace in Rau, service it, and drop it back — free of charge. Whether it's a routine periodic service, an oil change, AC service, or denting and painting, you get a clear quote up front and approve any extra work before it's done.</p>
<p class="md-body-md mb-3">For a mix of daily-drive hatchbacks and family sedans common to the area, we handle everything from small services to comprehensive checks. You watch the work happen and pay exactly what you were quoted — no hidden charges.</p>
<p class="md-body-md mb-3">Book online in minutes and pick a pickup slot that suits you.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/indore">Car servicing in Indore</a> · <a href="/car-servicing-in-vijay-nagar-indore">Vijay Nagar</a> · <a href="/car-servicing-in-ab-road-indore">AB Road</a> · <a href="/car-servicing-in-bhawarkua-indore">Bhawarkua</a> · <a href="/car-servicing-in-palasia-indore">Palasia</a> · <a href="/services/complete-car-servicing-in-indore">Complete car servicing in Indore</a></p>
</div>
HTML,
        ),
        'car-servicing-in-scheme-54-indore' => array(
            'title'            => 'Car Servicing in Scheme 54, Indore — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing in Scheme 54, Indore. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in Scheme 54, Indore',
            'intro'            => 'Need your car serviced in Scheme 54 without the hassle of a garage visit? Your Mechanic Online comes to you. Scheme 54 is a well-planned residential and commercial area near the Vijay Nagar side of Indore, close to AB Road, where parking and time are both at a premium — so having your car picked up, serviced, and dropped back makes life easier.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Need your car serviced in Scheme 54 without the hassle of a garage visit? Your Mechanic Online comes to you. Scheme 54 is a well-planned residential and commercial area near the Vijay Nagar side of Indore, close to AB Road, where parking and time are both at a premium — so having your car picked up, serviced, and dropped back makes life easier.</p>
<p class="md-body-md mb-3">Our free doorstep pickup covers all of Scheme 54. From periodic servicing and oil changes to AC service, brake work, and denting and painting, every job comes with a clear upfront quote, and you approve any additional parts before they're fitted.</p>
<p class="md-body-md mb-3">For the mix of premium sedans and well-kept city cars in the area, our mechanics handle both routine maintenance and detailed comprehensive services. You see the work being done and pay only what you agreed — nothing hidden.</p>
<p class="md-body-md mb-3">Book your slot online and we'll collect your car when it suits you.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/indore">Car servicing in Indore</a> · <a href="/car-servicing-in-vijay-nagar-indore">Vijay Nagar</a> · <a href="/car-servicing-in-nipania-indore">Nipania</a> · <a href="/car-servicing-in-palasia-indore">Palasia</a> · <a href="/car-servicing-in-bhawarkua-indore">Bhawarkua</a> · <a href="/services/complete-car-servicing-in-indore">Complete car servicing in Indore</a></p>
</div>
HTML,
        ),
        'car-servicing-in-old-palasia-indore' => array(
            'title'            => 'Car Servicing in Old Palasia, Indore — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing in Old Palasia, Indore. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in Old Palasia, Indore',
            'intro'            => 'Car servicing in Old Palasia is simpler when the workshop comes to you. Your Mechanic Online offers free doorstep pickup and drop across Old Palasia — one of Indore\'s established central neighbourhoods, busy with homes, offices and clinics, where finding time to sit at a garage is rarely easy.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Car servicing in Old Palasia is simpler when the workshop comes to you. Your Mechanic Online offers free doorstep pickup and drop across Old Palasia — one of Indore's established central neighbourhoods, busy with homes, offices and clinics, where finding time to sit at a garage is rarely easy.</p>
<p class="md-body-md mb-3">We collect your car, service it, and return it, all with pricing agreed before we start. Periodic service, oil and filter changes, AC service, brakes, denting and painting — whatever your car needs, you get a transparent quote and approve any extra work first.</p>
<p class="md-body-md mb-3">Our mechanics are equally comfortable with a quick routine service and a full comprehensive check. You can watch the work as it happens and you pay exactly what was quoted — no surprises added on.</p>
<p class="md-body-md mb-3">Book online, choose a convenient pickup time, and leave the rest to us.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/indore">Car servicing in Indore</a> · <a href="/car-servicing-in-palasia-indore">Palasia</a> · <a href="/car-servicing-in-vijay-nagar-indore">Vijay Nagar</a> · <a href="/car-servicing-in-bhawarkua-indore">Bhawarkua</a> · <a href="/car-servicing-in-nipania-indore">Nipania</a> · <a href="/services/complete-car-servicing-in-indore">Complete car servicing in Indore</a></p>
</div>
HTML,
        ),
        'car-servicing-in-sudama-nagar-indore' => array(
            'title'            => 'Car Servicing in Sudama Nagar, Indore — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing in Sudama Nagar, Indore. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in Sudama Nagar, Indore',
            'intro'            => 'If you\'re in Sudama Nagar and due for a car service, Your Mechanic Online brings it to your door. Sudama Nagar is a large residential colony in western Indore, where a doorstep pickup saves you the drive across town and the wait at a workshop.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">If you're in Sudama Nagar and due for a car service, Your Mechanic Online brings it to your door. Sudama Nagar is a large residential colony in western Indore, where a doorstep pickup saves you the drive across town and the wait at a workshop.</p>
<p class="md-body-md mb-3">We pick up your car free of charge, service it, and drop it back — with the price agreed before any work begins. From routine periodic servicing and oil changes to AC service, brake repairs, and denting and painting, you always get a clear quote and approve extras yourself.</p>
<p class="md-body-md mb-3">For the everyday hatchbacks and family cars common in the colony, our team handles both quick services and thorough comprehensive checks. You see exactly what's done and pay only what you were quoted.</p>
<p class="md-body-md mb-3">Book online in a couple of minutes and pick your pickup slot.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/indore">Car servicing in Indore</a> · <a href="/car-servicing-in-rau-indore">Rau</a> · <a href="/car-servicing-in-bhawarkua-indore">Bhawarkua</a> · <a href="/car-servicing-in-nipania-indore">Nipania</a> · <a href="/car-servicing-in-vijay-nagar-indore">Vijay Nagar</a> · <a href="/services/complete-car-servicing-in-indore">Complete car servicing in Indore</a></p>
</div>
HTML,
        ),
        'car-servicing-in-mahalaxmi-nagar-indore' => array(
            'title'            => 'Car Servicing in Mahalaxmi Nagar, Indore — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing in Mahalaxmi Nagar, Indore. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in Mahalaxmi Nagar, Indore',
            'intro'            => 'Car servicing in Mahalaxmi Nagar is easiest when you don\'t have to leave home for it. Your Mechanic Online offers free doorstep pickup and drop throughout Mahalaxmi Nagar — a growing residential area near the Vijay Nagar and bypass side of Indore.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Car servicing in Mahalaxmi Nagar is easiest when you don't have to leave home for it. Your Mechanic Online offers free doorstep pickup and drop throughout Mahalaxmi Nagar — a growing residential area near the Vijay Nagar and bypass side of Indore.</p>
<p class="md-body-md mb-3">We collect your car, carry out the service, and return it, with a clear quote agreed before we begin. Periodic servicing, oil changes, AC service, brakes, denting and painting — every job is transparent, and you approve any additional parts before they go in.</p>
<p class="md-body-md mb-3">Whether your car needs a simple routine service or a full comprehensive one, our mechanics handle it, and you can watch the work as it's done. You pay exactly what was quoted, with nothing added quietly to the bill.</p>
<p class="md-body-md mb-3">Book online and choose a pickup time that works for you.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/indore">Car servicing in Indore</a> · <a href="/car-servicing-in-vijay-nagar-indore">Vijay Nagar</a> · <a href="/car-servicing-in-scheme-54-indore">Scheme 54</a> · <a href="/car-servicing-in-nipania-indore">Nipania</a> · <a href="/car-servicing-in-bhawarkua-indore">Bhawarkua</a> · <a href="/services/complete-car-servicing-in-indore">Complete car servicing in Indore</a></p>
</div>
HTML,
        ),
        'car-servicing-in-gangapur-road-nashik' => array(
            'title'            => 'Car Servicing in Gangapur Road, Nashik — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing on Gangapur Road, Nashik. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in Gangapur Road, Nashik',
            'intro'            => 'Looking for dependable car servicing on Gangapur Road? Your Mechanic Online brings the service to your doorstep. Gangapur Road is one of Nashik\'s main residential arterial roads, lined with housing and busy through the day, so avoiding a garage trip and the wait is a real convenience.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Looking for dependable car servicing on Gangapur Road? Your Mechanic Online brings the service to your doorstep. Gangapur Road is one of Nashik's main residential arterial roads, lined with housing and busy through the day, so avoiding a garage trip and the wait is a real convenience.</p>
<p class="md-body-md mb-3">We pick up your car from your home or office along Gangapur Road, service it, and drop it back — free of charge. From periodic servicing and oil changes to AC service, brake work, and denting and painting, you get a clear quote up front and approve any extra work before it happens.</p>
<p class="md-body-md mb-3">For the mix of premium and family cars common along the road, our mechanics handle everything from quick services to detailed comprehensive checks. You watch the work and pay exactly what you were quoted — no hidden charges.</p>
<p class="md-body-md mb-3">Book online and pick a pickup slot that suits your day.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/nashik">Car servicing in Nashik</a> · <a href="/car-servicing-in-college-road-nashik">College Road</a> · <a href="/car-servicing-in-govind-nagar-nashik">Govind Nagar</a> · <a href="/car-servicing-in-panchavati-nashik">Panchavati</a> · <a href="/car-servicing-in-indira-nagar-nashik">Indira Nagar</a> · <a href="/services/complete-car-servicing-in-nashik">Complete car servicing in Nashik</a></p>
</div>
HTML,
        ),
        'car-servicing-in-indira-nagar-nashik' => array(
            'title'            => 'Car Servicing in Indira Nagar, Nashik — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing in Indira Nagar, Nashik. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in Indira Nagar, Nashik',
            'intro'            => 'Car servicing in Indira Nagar is simpler when the workshop comes to you. Your Mechanic Online offers free doorstep pickup and drop across Indira Nagar — a large, densely populated residential suburb of Nashik, where a doorstep service saves you both time and the trip across town.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Car servicing in Indira Nagar is simpler when the workshop comes to you. Your Mechanic Online offers free doorstep pickup and drop across Indira Nagar — a large, densely populated residential suburb of Nashik, where a doorstep service saves you both time and the trip across town.</p>
<p class="md-body-md mb-3">We collect your car, service it, and return it, with the price agreed before any work starts. Periodic servicing, oil and filter changes, AC service, brakes, denting and painting — everything comes with a transparent quote, and you approve extras before they're fitted.</p>
<p class="md-body-md mb-3">For the everyday hatchbacks and family cars common in the area, our mechanics handle routine and comprehensive services alike. You see the work as it happens and pay only what you agreed.</p>
<p class="md-body-md mb-3">Book online in minutes and choose your pickup time.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/nashik">Car servicing in Nashik</a> · <a href="/car-servicing-in-cidco-nashik">CIDCO</a> · <a href="/car-servicing-in-gangapur-road-nashik">Gangapur Road</a> · <a href="/car-servicing-in-college-road-nashik">College Road</a> · <a href="/car-servicing-in-govind-nagar-nashik">Govind Nagar</a> · <a href="/services/complete-car-servicing-in-nashik">Complete car servicing in Nashik</a></p>
</div>
HTML,
        ),
        'car-servicing-in-cidco-nashik' => array(
            'title'            => 'Car Servicing in CIDCO, Nashik — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing in CIDCO, Nashik. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in CIDCO, Nashik',
            'intro'            => 'If you\'re in CIDCO and your car is due for a service, Your Mechanic Online brings it to your door. CIDCO is a large planned township area of Nashik with a big residential population, where doorstep pickup means you skip the garage queue entirely.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">If you're in CIDCO and your car is due for a service, Your Mechanic Online brings it to your door. CIDCO is a large planned township area of Nashik with a big residential population, where doorstep pickup means you skip the garage queue entirely.</p>
<p class="md-body-md mb-3">We pick up your car free of charge, service it, and drop it back — with pricing agreed before we begin. From routine periodic servicing and oil changes to AC service, brake repairs, and denting and painting, you get a clear quote and approve any additional work yourself.</p>
<p class="md-body-md mb-3">Our mechanics handle both quick services and full comprehensive checks, and you can watch the work being done. You pay exactly what was quoted, with nothing added on quietly.</p>
<p class="md-body-md mb-3">Book online, pick a convenient pickup slot, and we'll take it from there.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/nashik">Car servicing in Nashik</a> · <a href="/car-servicing-in-indira-nagar-nashik">Indira Nagar</a> · <a href="/car-servicing-in-nashik-road">Nashik Road</a> · <a href="/car-servicing-in-deolali-nashik">Deolali</a> · <a href="/car-servicing-in-gangapur-road-nashik">Gangapur Road</a> · <a href="/services/complete-car-servicing-in-nashik">Complete car servicing in Nashik</a></p>
</div>
HTML,
        ),
        'car-servicing-in-deolali-nashik' => array(
            'title'            => 'Car Servicing in Deolali, Nashik — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing in Deolali, Nashik. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in Deolali, Nashik',
            'intro'            => 'Car servicing in Deolali is easier when you don\'t have to drive into the city for it. Your Mechanic Online offers free doorstep pickup and drop across Deolali — an established cantonment town to the south-east of Nashik, where a local doorstep service saves a longer trip to a city garage.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Car servicing in Deolali is easier when you don't have to drive into the city for it. Your Mechanic Online offers free doorstep pickup and drop across Deolali — an established cantonment town to the south-east of Nashik, where a local doorstep service saves a longer trip to a city garage.</p>
<p class="md-body-md mb-3">We collect your car, service it, and bring it back, with a clear quote agreed before any work begins. Periodic servicing, oil changes, AC service, brakes, denting and painting — every job is transparent, and you approve extras before they're done.</p>
<p class="md-body-md mb-3">Whether your car needs a routine service or a thorough comprehensive one, our mechanics handle it and you can watch the work happen. You pay exactly what you were quoted.</p>
<p class="md-body-md mb-3">Book online and choose a pickup time that suits you.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/nashik">Car servicing in Nashik</a> · <a href="/car-servicing-in-nashik-road">Nashik Road</a> · <a href="/car-servicing-in-cidco-nashik">CIDCO</a> · <a href="/car-servicing-in-mumbai-naka-nashik">Mumbai Naka</a> · <a href="/car-servicing-in-indira-nagar-nashik">Indira Nagar</a> · <a href="/services/complete-car-servicing-in-nashik">Complete car servicing in Nashik</a></p>
</div>
HTML,
        ),
        'car-servicing-in-mumbai-naka-nashik' => array(
            'title'            => 'Car Servicing in Mumbai Naka, Nashik — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing in Mumbai Naka, Nashik. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in Mumbai Naka, Nashik',
            'intro'            => 'Need your car serviced near Mumbai Naka without the hassle of a workshop visit? Your Mechanic Online comes to you. Mumbai Naka is a busy commercial junction and gateway area on the Mumbai side of Nashik, so a doorstep pickup lets you avoid the traffic and the wait.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Need your car serviced near Mumbai Naka without the hassle of a workshop visit? Your Mechanic Online comes to you. Mumbai Naka is a busy commercial junction and gateway area on the Mumbai side of Nashik, so a doorstep pickup lets you avoid the traffic and the wait.</p>
<p class="md-body-md mb-3">Our free doorstep service covers the Mumbai Naka area. From periodic servicing and oil changes to AC service, brake work, and denting and painting, every job comes with a clear upfront quote, and you approve any extra parts before they're fitted.</p>
<p class="md-body-md mb-3">Our mechanics handle both routine maintenance and detailed comprehensive services. You see the work being done and pay only what you agreed — nothing hidden on the bill.</p>
<p class="md-body-md mb-3">Book your slot online and we'll collect your car when it suits you.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/nashik">Car servicing in Nashik</a> · <a href="/car-servicing-in-gangapur-road-nashik">Gangapur Road</a> · <a href="/car-servicing-in-indira-nagar-nashik">Indira Nagar</a> · <a href="/car-servicing-in-nashik-road">Nashik Road</a> · <a href="/car-servicing-in-cidco-nashik">CIDCO</a> · <a href="/services/complete-car-servicing-in-nashik">Complete car servicing in Nashik</a></p>
</div>
HTML,
        ),
        'car-servicing-in-govind-nagar-nashik' => array(
            'title'            => 'Car Servicing in Govind Nagar, Nashik — Doorstep Pickup | YMO',
            'meta_description' => 'Doorstep car servicing in Govind Nagar, Nashik. Free pickup and drop, transparent pricing, expert mechanics. Book online with Your Mechanic Online.',
            'h1'               => 'Car servicing in Govind Nagar, Nashik',
            'intro'            => 'Car servicing in Govind Nagar is simpler when the workshop comes to your door. Your Mechanic Online offers free doorstep pickup and drop across Govind Nagar — a residential area close to the Gangapur Road side of Nashik.',
            'body'             => <<<'HTML'
<div class="ymo-content-section mb-5">
<p class="md-body-md mb-3">Car servicing in Govind Nagar is simpler when the workshop comes to your door. Your Mechanic Online offers free doorstep pickup and drop across Govind Nagar — a residential area close to the Gangapur Road side of Nashik.</p>
<p class="md-body-md mb-3">We collect your car, service it, and return it, with the price agreed before any work starts. Periodic servicing, oil and filter changes, AC service, brakes, denting and painting — everything is quoted transparently, and you approve any additional work before it's carried out.</p>
<p class="md-body-md mb-3">For the family cars and daily drivers common in the area, our mechanics handle routine and comprehensive services alike. You can watch the work as it happens and pay only what you were quoted.</p>
<p class="md-body-md mb-3">Book online in a couple of minutes and pick your pickup slot.</p>
<p class="md-body-md mt-3 mb-0"><a href="/locations/nashik">Car servicing in Nashik</a> · <a href="/car-servicing-in-gangapur-road-nashik">Gangapur Road</a> · <a href="/car-servicing-in-college-road-nashik">College Road</a> · <a href="/car-servicing-in-panchavati-nashik">Panchavati</a> · <a href="/car-servicing-in-indira-nagar-nashik">Indira Nagar</a> · <a href="/services/complete-car-servicing-in-nashik">Complete car servicing in Nashik</a></p>
</div>
HTML,
        ),
    ),
);
