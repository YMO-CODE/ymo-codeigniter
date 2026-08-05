#!/usr/bin/env python3
"""Generate marketing_pages_seo_growth.php for SEO growth implementation."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
OUT = ROOT / "application" / "config" / "marketing_pages_seo_growth.php"
TODAY = "2026-08-01"


def php_str(s: str) -> str:
    return "'" + s.replace("\\", "\\\\").replace("'", "\\'") + "'"


def locality_page(slug, title, meta, h1, qa, city, loc_slug, loc_label, landmarks, body_extra=""):
    faq = [
        (
            f"What is the cost of car servicing in {loc_label}?",
            f"Complete car servicing in {loc_label} starts from ₹1,999 at YMO. We share upfront estimates before work begins.",
        ),
        (
            f"Do you offer free pick-up in {loc_label}?",
            f"Yes. We collect your car from {loc_label} and surrounding {city.title()} areas at no extra charge.",
        ),
        (
            f"Which services are available in {loc_label}?",
            "Periodic service, AC repair, brake work, denting, polishing, interior cleaning, and engine diagnostics.",
        ),
    ]
    faq_php = ",\n            ".join(
        [f"array('q' => {php_str(q)}, 'a' => {php_str(a)})" for q, a in faq]
    )
    body = (
        f'<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Car service in {loc_label}, {city.title()}</h2>'
        f'<p class="md-body-md mb-3">{body_extra}</p>'
        f'<p class="md-body-md mb-0">Local landmarks and neighbourhoods we cover include {landmarks}. '
        f"YMO picks up your car, services it at our workshop, and returns it when done - with WhatsApp updates and photos throughout.</p></div>"
        f'<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Services in {loc_label}</h2>'
        f'<p class="md-body-md mb-3">From periodic maintenance to specialist repairs - one booking, free pick-up, transparent pricing.</p>'
        f'<ul class="md-body-md"><li class="mb-2">Complete car servicing from ₹1,999</li>'
        f'<li class="mb-2">AC repair and gas recharge</li><li class="mb-2">Brake inspection and replacement</li>'
        f'<li class="mb-2">Denting and painting from ₹3,000 per panel</li>'
        f'<li class="mb-2">Interior deep cleaning from ₹2,500</li>'
        f'<li class="mb-0">3-stage rubbing and polishing from ₹6,500</li></ul>'
        f'<p class="md-body-md mt-3 mb-0"><a href="/locations/{city}">All {city.title()} areas</a> · '
        f'<a href="/services">Service catalogue</a> · <a href="/brands">Brands we service</a></p></div>'
    )
    return f"""    {php_str(slug)} => array(
        'title'            => {php_str(title)},
        'meta_description' => {php_str(meta)},
        'h1'               => {php_str(h1)},
        'intro'            => {php_str('Expert car servicing in ' + loc_label + ' with free pick-up and transparent pricing.')},
        'quick_answer'     => {php_str(qa)},
        'body'             => {php_str(body)},
        'page_type'        => 'locality',
        'city_slug'        => {php_str(city)},
        'locality_slug'    => {php_str(loc_slug)},
        'locality_label'   => {php_str(loc_label)},
        'service_catalog'  => TRUE,
        'updated_at'       => {php_str(TODAY)},
        'faq'              => array(
            {faq_php}
        ),
        'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
        'view'             => 'marketing/page',
    ),"""


def service_area_page(slug, title, meta, h1, qa, city, loc_slug, loc_label, service_key, body_html):
    faq = [
        (
            f"Do you pick up from {loc_label} for this service?",
            f"Yes. Free pick-up and drop from {loc_label} and nearby {city.title()} areas.",
        ),
        (
            f"How much does this cost in {loc_label}?",
            "We share an upfront estimate before starting. Pricing depends on your car model and condition.",
        ),
    ]
    faq_php = ",\n            ".join(
        [f"array('q' => {php_str(q)}, 'a' => {php_str(a)})" for q, a in faq]
    )
    return f"""    {php_str(slug)} => array(
        'title'            => {php_str(title)},
        'meta_description' => {php_str(meta)},
        'h1'               => {php_str(h1)},
        'intro'            => {php_str(h1)},
        'quick_answer'     => {php_str(qa)},
        'body'             => {php_str(body_html)},
        'page_type'        => 'service',
        'city_slug'        => {php_str(city)},
        'locality_slug'    => {php_str(loc_slug)},
        'locality_label'   => {php_str(loc_label)},
        'service_key'      => {php_str(service_key)},
        'updated_at'       => {php_str(TODAY)},
        'faq'              => array(
            {faq_php}
        ),
        'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
        'view'             => 'marketing/page',
    ),"""


def blog_post(slug, title, meta, h1, qa, body):
    return f"""    {php_str(slug)} => array(
        'title'            => {php_str(title)},
        'meta_description' => {php_str(meta)},
        'h1'               => {php_str(h1)},
        'intro'            => {php_str(qa[:160])},
        'quick_answer'     => {php_str(qa)},
        'body'             => {php_str(body)},
        'page_type'        => 'blog',
        'city_slug'        => 'pune',
        'updated_at'       => {php_str(TODAY)},
        'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
        'view'             => 'marketing/page',
    ),"""


def main():
    pages = []

    indore_body = (
        '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Indore neighbourhoods we serve</h2>'
        '<p class="md-body-md mb-4">Your Mechanic Online is among the best car service providers in Indore - affordable denting, painting, AC repair, periodic servicing, and free pick-up/drop with transparent pricing across the city.</p>'
        '<div class="row g-4">'
        '<div class="col-md-6 col-lg-4"><a href="/car-servicing-in-vijay-nagar-indore" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
        '<span class="mi mi-xl md-icon-primary">location_on</span><h3 class="md-title-md mt-3 mb-1 text-dark">Vijay Nagar</h3>'
        '<p class="md-body-md mb-0">Car servicing in Vijay Nagar with free pick-up and expert mechanics.</p></a></div>'
        '<div class="col-md-6 col-lg-4"><a href="/car-servicing-in-palasia-indore" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
        '<span class="mi mi-xl md-icon-primary">location_on</span><h3 class="md-title-md mt-3 mb-1 text-dark">Palasia</h3>'
        '<p class="md-body-md mb-0">Trusted car care in Palasia - periodic service, AC, and denting.</p></a></div>'
        '<div class="col-md-6 col-lg-4"><a href="/car-servicing-in-bhawarkua-indore" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
        '<span class="mi mi-xl md-icon-primary">location_on</span><h3 class="md-title-md mt-3 mb-1 text-dark">Bhawarkua</h3>'
        '<p class="md-body-md mb-0">Affordable car services in Bhawarkua with same-day options.</p></a></div>'
        '<div class="col-md-6 col-lg-4"><a href="/car-servicing-in-ab-road-indore" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
        '<span class="mi mi-xl md-icon-primary">location_on</span><h3 class="md-title-md mt-3 mb-1 text-dark">AB Road</h3>'
        '<p class="md-body-md mb-0">Complete car servicing along AB Road and connecting areas.</p></a></div>'
        '<div class="col-md-6 col-lg-4"><a href="/car-servicing-in-nipania-indore" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
        '<span class="mi mi-xl md-icon-primary">location_on</span><h3 class="md-title-md mt-3 mb-1 text-dark">Nipania</h3>'
        '<p class="md-body-md mb-0">Doorstep pick-up for Nipania residents and nearby townships.</p></a></div>'
        '</div></div>'
        '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Why choose YMO in Indore?</h2>'
        '<p class="md-body-md mb-4">Indore drivers choose YMO for workshop-grade capability - not just a quick home visit. We handle denting, painting, AC compressor work, and luxury cars alongside everyday hatchback servicing.</p>'
        '<div class="row g-4">'
        '<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">engineering</span>'
        '<h3 class="md-title-md mt-3 mb-2">Expert technicians</h3><p class="md-body-md mb-0">Trained mechanics for Maruti, Hyundai, Tata, and premium brands.</p></div></div>'
        '<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">payments</span>'
        '<h3 class="md-title-md mt-3 mb-2">Upfront pricing</h3><p class="md-body-md mb-0">Clear estimates from ₹1,999 - no surprise add-ons at billing.</p></div></div>'
        '<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">local_shipping</span>'
        '<h3 class="md-title-md mt-3 mb-2">Free pick-up</h3><p class="md-body-md mb-0">We collect and return your car across Vijay Nagar, Palasia, Bhawarkua, and more.</p></div></div>'
        '<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">schedule</span>'
        '<h3 class="md-title-md mt-3 mb-2">Same-day service</h3><p class="md-body-md mb-0">Most maintenance jobs completed the same day you book.</p></div></div>'
        '</div>'
        '<p class="md-body-md mt-4 mb-0">Browse <a href="/brands">brand-specific service pages</a> or <a href="/services/complete-car-servicing-in-indore">complete car servicing in Indore</a>.</p></div>'
    )

    pages.append(
        f"""    'locations/indore' => array(
        'title'            => 'Car Servicing in Indore - Your Mechanic Online',
        'meta_description' => 'Affordable car servicing in Indore - Vijay Nagar, Palasia, Bhawarkua, AB Road, Nipania. Denting, AC repair, periodic service from ₹1,999 with free pick-up.',
        'h1'               => 'Car servicing in Indore',
        'intro'            => 'Professional car care and transparent pricing for Indore drivers.',
        'quick_answer'     => 'Your Mechanic Online is among the best car service providers in Indore, offering affordable denting, painting, AC repair, periodic servicing, and free pick-up/drop with transparent pricing.',
        'body'             => {php_str(indore_body)},
        'page_type'        => 'hub',
        'city_slug'        => 'indore',
        'service_catalog'  => TRUE,
        'service_catalog_heading' => 'Car services in Indore',
        'updated_at'       => '{TODAY}',
        'faq'              => array(
            array('q' => 'What is the cost of car servicing in Indore?', 'a' => 'Car servicing in Indore starts from ₹1,999 for periodic maintenance. YMO shares upfront estimates for denting, AC repair, and other jobs before starting work.'),
            array('q' => 'Do you provide same-day car service in Indore?', 'a' => 'Yes. Most maintenance and repair jobs are completed the same day. Book online or call +91-7744-065904.'),
            array('q' => 'Which areas in Indore do you serve?', 'a' => 'We serve Vijay Nagar, Palasia, Bhawarkua, AB Road, Nipania, Deoguradia, Rau, Scheme 54, and surrounding Indore areas with free pick-up and drop.'),
        ),
        'og_image'         => '/assets/img/marketing/2022/03/974159.jpg',
        'view'             => 'marketing/page',
    ),"""
    )

    nashik_body = (
        '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Nashik neighbourhoods we serve</h2>'
        '<p class="md-body-md mb-4">Your Mechanic Online provides expert car servicing and repairs in Nashik with trained mechanics, transparent pricing, and doorstep pick-up across College Road, Nashik Road, Panchavati, and surrounding areas.</p>'
        '<div class="row g-4">'
        '<div class="col-md-6 col-lg-4"><a href="/car-servicing-in-college-road-nashik" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
        '<span class="mi mi-xl md-icon-primary">location_on</span><h3 class="md-title-md mt-3 mb-1 text-dark">College Road</h3>'
        '<p class="md-body-md mb-0">Car servicing on College Road with free pick-up and expert care.</p></a></div>'
        '<div class="col-md-6 col-lg-4"><a href="/car-servicing-in-nashik-road" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
        '<span class="mi mi-xl md-icon-primary">location_on</span><h3 class="md-title-md mt-3 mb-1 text-dark">Nashik Road</h3>'
        '<p class="md-body-md mb-0">Affordable car services near Nashik Road railway station area.</p></a></div>'
        '<div class="col-md-6 col-lg-4"><a href="/car-servicing-in-panchavati-nashik" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
        '<span class="mi mi-xl md-icon-primary">location_on</span><h3 class="md-title-md mt-3 mb-1 text-dark">Panchavati</h3>'
        '<p class="md-body-md mb-0">Trusted car care in Panchavati - periodic service and AC repair.</p></a></div>'
        '<div class="col-md-6 col-lg-4"><a href="/car-servicing-in-dwarka-nashik" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
        '<span class="mi mi-xl md-icon-primary">location_on</span><h3 class="md-title-md mt-3 mb-1 text-dark">Dwarka</h3>'
        '<p class="md-body-md mb-0">Doorstep car servicing in Dwarka and nearby Nashik areas.</p></a></div>'
        '<div class="col-md-6 col-lg-4"><a href="/car-servicing-in-uday-nagar-nashik" class="md-card-elevated h-100 d-block text-decoration-none marketing-service-card">'
        '<span class="mi mi-xl md-icon-primary">location_on</span><h3 class="md-title-md mt-3 mb-1 text-dark">Uday Nagar</h3>'
        '<p class="md-body-md mb-0">Complete car care in Uday Nagar with transparent pricing.</p></a></div>'
        '</div></div>'
        '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Why choose YMO in Nashik?</h2>'
        '<p class="md-body-md mb-4">Nashik drivers get full workshop services - denting, painting, AC gas recharge, engine diagnostics - with the convenience of free pick-up and WhatsApp updates, not just a mechanic at your gate.</p>'
        '<div class="row g-4">'
        '<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">engineering</span>'
        '<h3 class="md-title-md mt-3 mb-2">Expert technicians</h3><p class="md-body-md mb-0">Modern diagnostic equipment and trained mechanics for all brands.</p></div></div>'
        '<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">payments</span>'
        '<h3 class="md-title-md mt-3 mb-2">Transparent pricing</h3><p class="md-body-md mb-0">Upfront estimates from ₹1,999 - know the cost before we start.</p></div></div>'
        '<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">local_shipping</span>'
        '<h3 class="md-title-md mt-3 mb-2">Free pick-up</h3><p class="md-body-md mb-0">We collect and return your car across College Road, Nashik Road, and Panchavati.</p></div></div>'
        '<div class="col-md-6 col-lg-3"><div class="md-card-elevated h-100"><span class="mi mi-xl md-icon-primary">auto_fix_high</span>'
        '<h3 class="md-title-md mt-3 mb-2">Body &amp; AC work</h3><p class="md-body-md mb-0">Denting from ₹3,000/panel, AC repair, and polishing under one roof.</p></div></div>'
        '</div>'
        '<p class="md-body-md mt-4 mb-0">See <a href="/brands">brands we service</a> or <a href="/services/complete-car-servicing-in-nashik">complete car servicing in Nashik</a>.</p></div>'
    )

    pages.append(
        f"""    'locations/nashik' => array(
        'title'            => 'Car Servicing in Nashik - Your Mechanic Online',
        'meta_description' => 'Expert car servicing in Nashik - College Road, Nashik Road, Panchavati, Dwarka, Uday Nagar. Free pick-up, AC repair, denting from ₹1,999.',
        'h1'               => 'Car servicing in Nashik',
        'intro'            => 'Expert car care and repairs for Nashik drivers with free pick-up.',
        'quick_answer'     => 'Your Mechanic Online provides expert car servicing and repairs in Nashik with trained mechanics, transparent pricing, and doorstep pick-up across College Road, Nashik Road, Uday Nagar, and surrounding areas.',
        'body'             => {php_str(nashik_body)},
        'page_type'        => 'hub',
        'city_slug'        => 'nashik',
        'service_catalog'  => TRUE,
        'service_catalog_heading' => 'Car services in Nashik',
        'updated_at'       => '{TODAY}',
        'faq'              => array(
            array('q' => 'Where can I find a reliable car mechanic in Nashik?', 'a' => 'Your Mechanic Online offers trained mechanics, modern diagnostic equipment, and upfront pricing for car repair and servicing across Nashik.'),
            array('q' => 'Do you serve Nashik Road and College Road?', 'a' => 'Yes. We provide free pick-up and drop from Nashik Road, College Road, Uday Nagar, Panchavati, and nearby neighbourhoods.'),
            array('q' => 'What car services are available in Nashik?', 'a' => 'Complete servicing, AC repair, brake repair, denting and painting, engine diagnostics, interior cleaning, rubbing and polishing, belts and hoses, and oil changes.'),
        ),
        'og_image'         => '/assets/img/marketing/revslider/main/image_021.jpg',
        'view'             => 'marketing/page',
    ),"""
    )

    pages.append(
        locality_page(
            "car-servicing-in-vijay-nagar-indore",
            "Car Servicing in Vijay Nagar, Indore | YMO",
            "Car service in Vijay Nagar Indore from ₹1,999. Free pick-up, AC repair, denting, periodic maintenance. Book YMO online.",
            "Car servicing in Vijay Nagar, Indore",
            "YMO offers car servicing in Vijay Nagar, Indore from ₹1,999 with free pick-up, expert mechanics, and transparent pricing.",
            "indore",
            "vijay_nagar",
            "Vijay Nagar",
            "Scheme 54, Palasia Square, and BRTS corridor",
            "Vijay Nagar is one of Indore's busiest commercial and residential hubs. Whether you commute on AB Road or park near high-street retail, YMO picks up your car for full workshop servicing - not just a quick check at your gate.",
        )
    )
    pages.append(
        locality_page(
            "car-servicing-in-palasia-indore",
            "Car Servicing in Palasia, Indore | YMO",
            "Trusted car servicing in Palasia Indore. Periodic service, AC repair, denting from ₹1,999 with free doorstep pick-up.",
            "Car servicing in Palasia, Indore",
            "Expert car servicing in Palasia, Indore with free pick-up, same-day options, and upfront pricing from ₹1,999.",
            "indore",
            "palasia",
            "Palasia",
            "Palasia Square, MG Road, and nearby Old Palasia lanes",
            "Palasia's central location means tight parking and heavy traffic - ideal for YMO's pick-up model. We service hatchbacks, sedans, and SUVs for residents and professionals across Old and New Palasia.",
        )
    )
    pages.append(
        locality_page(
            "car-servicing-in-bhawarkua-indore",
            "Car Servicing in Bhawarkua, Indore | YMO",
            "Affordable car service in Bhawarkua Indore. Free pick-up, AC repair, denting, complete servicing from ₹1,999.",
            "Car servicing in Bhawarkua, Indore",
            "Affordable car servicing in Bhawarkua, Indore - periodic maintenance, AC, brakes, and body work with free pick-up.",
            "indore",
            "bhawarkua",
            "Bhawarkua",
            "Ring Road junction, educational institutes, and residential colonies",
            "Bhawarkua's growing residential belt deserves reliable car care without long garage queues. YMO collects your vehicle, completes service at our workshop, and delivers it back with photo updates on WhatsApp.",
        )
    )
    pages.append(
        locality_page(
            "car-servicing-in-ab-road-indore",
            "Car Servicing on AB Road, Indore | YMO",
            "Car service on AB Road Indore - free pick-up, periodic servicing from ₹1,999, AC repair and denting.",
            "Car servicing on AB Road, Indore",
            "Car servicing along AB Road, Indore with free pick-up, expert mechanics, and transparent pricing from ₹1,999.",
            "indore",
            "ab_road",
            "AB Road",
            "Industry House, Satya Sai Square, and AB Road flyover corridor",
            "AB Road is Indore's main commercial artery. YMO serves offices, showrooms, and commuters along the stretch with pick-up from your workplace or home - full workshop capability included.",
        )
    )
    pages.append(
        locality_page(
            "car-servicing-in-nipania-indore",
            "Car Servicing in Nipania, Indore | YMO",
            "Car service in Nipania Indore and surrounding townships. Free pick-up, complete servicing from ₹1,999.",
            "Car servicing in Nipania, Indore",
            "Doorstep car servicing in Nipania, Indore - periodic service, AC repair, and denting with free pick-up and drop.",
            "indore",
            "nipania",
            "Nipania",
            "Nipania township, Super Corridor, and adjoining residential projects",
            "Nipania's fast-growing township area is home to many families who prefer booking service online. YMO's free pick-up means no trip to the inner city - we handle everything and return your car serviced.",
        )
    )

    pages.append(
        locality_page(
            "car-servicing-in-college-road-nashik",
            "Car Servicing on College Road, Nashik | YMO",
            "Car service on College Road Nashik from ₹1,999. Free pick-up, AC repair, denting, periodic maintenance.",
            "Car servicing on College Road, Nashik",
            "Expert car servicing on College Road, Nashik with free pick-up, transparent pricing, and same-day options.",
            "nashik",
            "college_road",
            "College Road",
            "College Road cafes, residential complexes, and connecting lanes to Gangapur Road",
            "College Road is a popular Nashik neighbourhood for families and professionals. YMO provides full workshop servicing with pick-up from your building or office - covering periodic maintenance through to denting and AC work.",
        )
    )
    pages.append(
        locality_page(
            "car-servicing-in-nashik-road",
            "Car Servicing on Nashik Road | YMO",
            "Car service on Nashik Road from ₹1,999. Free pick-up near the railway station area, AC repair, and denting.",
            "Car servicing on Nashik Road, Nashik",
            "Affordable car servicing on Nashik Road with free pick-up, expert mechanics, and upfront pricing from ₹1,999.",
            "nashik",
            "nashik_road",
            "Nashik Road",
            "Nashik Road station area, Bytco Point, and industrial belt connectors",
            "Nashik Road commuters and businesses rely on dependable car care. YMO collects your vehicle from the station area or nearby colonies, services it at our workshop, and returns it on schedule.",
        )
    )
    pages.append(
        locality_page(
            "car-servicing-in-panchavati-nashik",
            "Car Servicing in Panchavati, Nashik | YMO",
            "Car service in Panchavati Nashik from ₹1,999. Free pick-up, periodic service, AC repair, and denting.",
            "Car servicing in Panchavati, Nashik",
            "Trusted car servicing in Panchavati, Nashik - free pick-up, same-day service, and transparent pricing.",
            "nashik",
            "panchavati",
            "Panchavati",
            "Panchavati Karanja, Godavari ghats vicinity, and old city connectors",
            "Panchavati's mix of old-city lanes and newer developments needs flexible pick-up. YMO handles Maruti, Hyundai, Tata, and premium brands with workshop-grade denting and AC repair - not limited to basic home visits.",
        )
    )
    pages.append(
        locality_page(
            "car-servicing-in-dwarka-nashik",
            "Car Servicing in Dwarka, Nashik | YMO",
            "Car service in Dwarka Nashik from ₹1,999. Free pick-up, complete servicing, AC repair, and denting.",
            "Car servicing in Dwarka, Nashik",
            "Doorstep car servicing in Dwarka, Nashik with free pick-up and expert mechanics from ₹1,999.",
            "nashik",
            "dwarka",
            "Dwarka",
            "Dwarka residential zone, Adgaon connector, and nearby Nashik suburbs",
            "Dwarka's growing residential area benefits from YMO online booking and free pick-up. We service all major brands and return your car with a clear invoice and service checklist.",
        )
    )
    pages.append(
        locality_page(
            "car-servicing-in-uday-nagar-nashik",
            "Car Servicing in Uday Nagar, Nashik | YMO",
            "Car service in Uday Nagar Nashik from ₹1,999. Free pick-up, periodic maintenance, AC and brake repair.",
            "Car servicing in Uday Nagar, Nashik",
            "Affordable car servicing in Uday Nagar, Nashik - free pick-up, transparent pricing, and expert technicians.",
            "nashik",
            "uday_nagar",
            "Uday Nagar",
            "Uday Nagar colony, College Road proximity, and Nashik city connectors",
            "Uday Nagar residents choose YMO for hassle-free servicing without waiting at a garage. Book online, we pick up, service at our workshop, and deliver back - with WhatsApp photo updates.",
        )
    )

    svc_areas = [
        (
            "car-ac-repair-in-baner",
            "Car AC Repair in Baner, Pune | YMO",
            "Car AC repair in Baner Pune - gas recharge, leak test, compressor check. Free pick-up from Baner and Balewadi.",
            "Car AC repair in Baner, Pune",
            "YMO offers car AC repair in Baner with leak testing, gas recharge, compressor service, and free pick-up from Baner and Balewadi.",
            "pune",
            "baner",
            "Baner",
            "ac",
            "Baner's summer heat makes a working car AC essential. YMO AC service in Baner includes system pressure test, leak detection, compressor inspection, filter cleaning, and gas top-up or recharge using proper equipment - not a portable kit at your parking spot. We pick up from Baner, Balewadi High Street, and surrounding societies, complete AC work at our workshop, and return your car blowing cold again. Common Baner issues include weak cooling after monsoon, compressor clutch noise, and condenser damage from road debris. Related: <a href=\"/the-best-car-servicing-in-baner\">car servicing in Baner</a>, <a href=\"/services/car-air-conditioner-servicing-in-pune\">AC servicing in Pune</a>, <a href=\"/maruti-suzuki-car-service-in-pune\">Maruti service in Pune</a>.",
        ),
        (
            "car-denting-and-painting-in-wakad",
            "Car Denting & Painting in Wakad, Pune | YMO",
            "Car denting and painting in Wakad Pune from ₹3,000/panel. Colour matching, free pick-up, industry-approved paint.",
            "Car denting & painting in Wakad, Pune",
            "Panel dent repair and car painting in Wakad from ₹3,000 per panel - colour matching, free pick-up, and workshop-quality finish.",
            "pune",
            "wakad",
            "Wakad",
            "denting",
            "Wakad's tight parking and highway commutes mean dents and scratches are common. YMO denting and painting in Wakad covers panel repair, bumper restoration, scratch removal, and full panel respray with industry-approved paints and colour matching. Unlike a doorstep mechanic who cannot spray in your society parking, we pick up your car, repair it in a controlled booth environment, and deliver it back. Pricing starts from ₹3,000 per panel with an upfront estimate before work. See also: <a href=\"/affordable-car-servicing-in-wakad-pune\">car servicing in Wakad</a>, <a href=\"/services/car-denting-and-painting-3000\">denting in Pune</a>.",
        ),
        (
            "car-polishing-in-hinjewadi",
            "Car Polishing in Hinjewadi, Pune | YMO",
            "3-stage car rubbing and polishing in Hinjewadi from ₹6,500. Restore gloss, remove fine scratches. Free pick-up.",
            "Car polishing in Hinjewadi, Pune",
            "Professional 3-stage rubbing and polishing in Hinjewadi from ₹6,500 - restore gloss and remove fine scratches with free pick-up.",
            "pune",
            "hinjewadi",
            "Hinjewadi",
            "polishing",
            "Hinjewadi IT corridor cars face sun exposure on open parking and fine scratches from daily commutes. YMO's 3-stage rubbing and polishing in Hinjewadi restores paint gloss, removes swirl marks, and protects the finish - work that requires proper lighting and equipment at our workshop. Package from ₹6,500 includes pick-up from Hinjewadi Phase 1–3, Maan, and surrounding tech parks. Pair with <a href=\"/best-car-services-hinjewadi-pune\">car servicing in Hinjewadi</a> for a full refresh.",
        ),
        (
            "car-brake-repair-in-aundh",
            "Car Brake Repair in Aundh, Pune | YMO",
            "Car brake repair in Aundh Pune - pads, rotors, fluid check. Free pick-up from Aundh and University Road area.",
            "Car brake repair in Aundh, Pune",
            "Expert brake repair in Aundh - pad replacement, rotor inspection, fluid top-up, and free pick-up from Aundh and nearby areas.",
            "pune",
            "aundh",
            "Aundh",
            "brakes",
            "Squealing brakes on Aundh hill roads or University Road traffic need proper inspection - not just a quick pad glance. YMO brake service in Aundh covers pad and shoe replacement, rotor/skimming assessment, caliper check, and brake fluid condition. We pick up from Aundh Gaon, D P Road, and surrounding areas, complete work at our workshop, and road-test before return. Link: <a href=\"/car-servicing-in-aundh\">car servicing in Aundh</a>, <a href=\"/services/car-brake-repair\">brake repair in Pune</a>.",
        ),
        (
            "car-ac-repair-in-viman-nagar",
            "Car AC Repair in Viman Nagar, Pune | YMO",
            "Car AC repair in Viman Nagar Pune - gas recharge, compressor service. Free pick-up from Viman Nagar and Kalyani Nagar.",
            "Car AC repair in Viman Nagar, Pune",
            "Car AC repair in Viman Nagar with gas recharge, leak test, and compressor check - free pick-up from Viman Nagar and Kalyani Nagar.",
            "pune",
            "viman_nagar",
            "Viman Nagar",
            "ac",
            "Viman Nagar airport-area traffic and summer heat strain car AC systems. YMO services Viman Nagar and Kalyani Nagar with full AC diagnostics - leak test, gas recharge, compressor repair, and cabin filter replacement. We pick up from residential complexes and business parks, avoiding the need to sit at a local garage. Also see: <a href=\"/affordable-car-services-viman-nagar-pune\">car servicing in Viman Nagar</a>, <a href=\"/hyundai-car-service-in-pune\">Hyundai service in Pune</a>.",
        ),
    ]
    for slug, title, meta, h1, qa, city, loc, label, skey, para in svc_areas:
        body = (
            f'<div class="ymo-content-section mb-5"><p class="md-body-md">{para}</p></div>'
            '<div class="ymo-content-section"><div class="table-responsive"><table class="table md-body-md">'
            "<thead><tr><th>Service</th><th>From</th></tr></thead><tbody>"
            "<tr><td>Workshop service with pick-up</td><td>Free pick-up</td></tr>"
            "<tr><td>Upfront estimate</td><td>Before work starts</td></tr>"
            "</tbody></table></div></div>"
        )
        pages.append(service_area_page(slug, title, meta, h1, qa, city, loc, label, skey, body))

    blogs = [
        (
            "blog/maruti-swift-service-cost-pune-2026",
            "Maruti Swift Service Cost in Pune (2026 Guide) | YMO",
            "How much does a Maruti Swift service cost in Pune in 2026? Complete guide to periodic service prices, what is included, and how to book.",
            "How much does a Maruti Swift service cost in Pune? (2026 guide)",
            "Maruti Swift periodic service in Pune starts from ₹1,999 at YMO - covering oil change, filters, brake cleaning, AC filter, wash, and free pick-up.",
            '<div class="ymo-content-section mb-5"><p class="md-body-md">If you own a Maruti Swift in Pune, you have probably wondered what a fair price for periodic service looks like in 2026. Authorised service centres charge a premium; local garages may skip steps. YMO sits in the middle - workshop-quality work from ₹1,999 with free pick-up.</p>'
            '<h2 class="md-headline-md mt-4 mb-3">Typical Swift service costs in Pune (2026)</h2>'
            '<div class="table-responsive"><table class="table md-body-md"><thead><tr><th>Service type</th><th>Indicative cost</th></tr></thead><tbody>'
            "<tr><td>Basic periodic service (oil, filters, wash)</td><td>₹1,999</td></tr>"
            "<tr><td>AC service / gas recharge</td><td>₹1,500 – ₹4,000</td></tr>"
            "<tr><td>Brake pad replacement</td><td>₹2,000 – ₹5,000</td></tr>"
            "<tr><td>Denting (per panel)</td><td>From ₹3,000</td></tr>"
            "</tbody></table></div>"
            "<p class=\"md-body-md mt-3\">Prices vary by mileage, parts needed, and Swift variant (petrol vs CNG). YMO always shares an estimate before starting.</p>"
            '<h2 class="md-headline-md mt-4 mb-3">What is included in YMO\'s ₹1,999 package?</h2>'
            "<ul class=\"md-body-md\"><li>Engine oil and filter change</li><li>Air filter check/replace if needed</li>"
            "<li>Brake cleaning and greasing</li><li>AC filter cleaning</li><li>Coolant and fluid top-ups</li>"
            "<li>Interior vacuum and exterior wash</li><li>Free pick-up and delivery across Pune</li></ul>"
            '<p class="md-body-md">Read more: <a href="/maruti-suzuki-car-service-in-pune">Maruti Suzuki car service in Pune</a> · '
            '<a href="/services/complete-car-servicing-in-pune">Complete car servicing</a></p></div>',
        ),
        (
            "blog/car-ac-gas-recharge-cost-pune-2026",
            "Car AC Gas Recharge Cost in Pune - 2026 Guide | YMO",
            "Car AC gas recharge cost in Pune explained - what affects price, when to recharge, and how YMO AC service works with free pick-up.",
            "Car AC gas recharge cost in Pune - complete 2026 guide",
            "Car AC gas recharge in Pune typically costs ₹1,500–₹4,000 depending on gas type and leaks - YMO includes leak test and pick-up.",
            '<div class="ymo-content-section mb-5"><p class="md-body-md">Weak AC cooling in Pune\'s summer usually means low gas, a leak, or a failing compressor. Understanding recharge costs helps you avoid overpaying or accepting a temporary top-up without fixing the root cause.</p>'
            '<h2 class="md-headline-md mt-4 mb-3">What affects AC recharge cost?</h2>'
            "<ul class=\"md-body-md\"><li><strong>Gas type</strong> - R134a vs R1234yf (newer cars)</li>"
            "<li><strong>Leak repair</strong> - topping up without fixing a leak wastes money</li>"
            "<li><strong>Compressor condition</strong> - may need repair beyond gas alone</li>"
            "<li><strong>Filter and condenser</strong> - blocked condenser reduces cooling efficiency</li></ul>"
            '<h2 class="md-headline-md mt-4 mb-3">Typical price range in Pune</h2>'
            '<div class="table-responsive"><table class="table md-body-md"><thead><tr><th>Job</th><th>Range</th></tr></thead><tbody>'
            "<tr><td>Leak test + gas recharge</td><td>₹1,500 – ₹4,000</td></tr>"
            "<tr><td>Compressor repair</td><td>₹5,000 – ₹15,000+</td></tr>"
            "</tbody></table></div>"
            '<p class="md-body-md mt-3">YMO picks up your car, diagnoses properly at our workshop, and shares an upfront quote. See '
            '<a href="/services/car-air-conditioner-servicing-in-pune">AC servicing in Pune</a> and '
            '<a href="/car-ac-repair-in-baner">AC repair in Baner</a>.</p></div>',
        ),
        (
            "blog/hyundai-i20-service-cost-pune",
            "Hyundai i20 Service Cost in Pune: What to Expect | YMO",
            "Hyundai i20 service cost in Pune - periodic maintenance prices, DCT/CVT notes, and what YMO includes from ₹1,999.",
            "Hyundai i20 service cost in Pune: what to expect",
            "Hyundai i20 periodic service in Pune starts from ₹1,999 at YMO with oil change, filters, brake check, wash, and free pick-up.",
            '<div class="ymo-content-section mb-5"><p class="md-body-md">The Hyundai i20 is one of Pune\'s most popular hatchbacks. Service costs depend on petrol vs diesel, manual vs iMT/DCT, and whether you are due for a major interval at 40,000 km or beyond.</p>'
            '<h2 class="md-headline-md mt-4 mb-3">i20 service price guide</h2>'
            '<div class="table-responsive"><table class="table md-body-md"><thead><tr><th>Service</th><th>From</th></tr></thead><tbody>'
            "<tr><td>Periodic service</td><td>₹1,999</td></tr>"
            "<tr><td>AC service</td><td>₹1,500+</td></tr>"
            "<tr><td>DCT fluid check (if applicable)</td><td>On inspection</td></tr>"
            "</tbody></table></div>"
            '<p class="md-body-md mt-3">Common i20 issues include AC cooling drop, DCT shudder at low speed, and rear suspension noise on speed breakers - all diagnosable at YMO\'s workshop. '
            '<a href="/hyundai-car-service-in-pune">Hyundai car service in Pune</a></p></div>',
        ),
        (
            "blog/car-denting-painting-cost-pune",
            "Car Denting & Painting Cost in Pune - Price per Panel | YMO",
            "Car denting and painting cost in Pune explained - per panel pricing from ₹3,000, factors that affect quotes, and YMO pick-up service.",
            "Car denting & painting cost in Pune - price per panel explained",
            "Car denting and painting in Pune starts from ₹3,000 per panel at YMO with colour matching, free pick-up, and industry-approved paint.",
            '<div class="ymo-content-section mb-5"><p class="md-body-md">A door ding or bumper scrape does not have to mean weeks at a body shop. YMO denting and painting in Pune starts from ₹3,000 per panel - with pick-up so you do not wait at the garage.</p>'
            '<h2 class="md-headline-md mt-4 mb-3">Per-panel pricing factors</h2>'
            "<ul class=\"md-body-md\"><li>Panel size and damage depth</li><li>Alloy vs steel body work</li>"
            "<li>Pearl/metallic paint vs solid colour</li><li>Bumper vs door vs bonnet labour</li></ul>"
            '<div class="table-responsive mt-3"><table class="table md-body-md"><thead><tr><th>Work</th><th>From</th></tr></thead><tbody>'
            "<tr><td>Minor dent + touch-up</td><td>₹3,000/panel</td></tr>"
            "<tr><td>Full panel respray</td><td>₹4,000 – ₹8,000</td></tr>"
            "<tr><td>Bumper repair + paint</td><td>₹3,500+</td></tr>"
            "</tbody></table></div>"
            '<p class="md-body-md mt-3"><a href="/services/car-denting-and-painting-3000">Denting & painting in Pune</a> · '
            '<a href="/car-denting-and-painting-in-wakad">Denting in Wakad</a></p></div>',
        ),
        (
            "blog/complete-vs-periodic-car-service",
            "Complete vs Periodic Car Service: What's the Difference? | YMO",
            "Complete car service vs periodic service explained - what each includes, when you need which, and costs in Pune from ₹1,999.",
            "Complete car service vs periodic service: what's the difference?",
            "Periodic service covers oil, filters, and basic checks from ₹1,999; complete service adds deeper inspection items based on mileage and manufacturer schedule.",
            '<div class="ymo-content-section mb-5"><p class="md-body-md">Manufacturers use different names - periodic, general, comprehensive - which confuses drivers. Here is a practical breakdown for Pune car owners.</p>'
            '<h2 class="md-headline-md mt-4 mb-3">Periodic service (every 10,000 km / 12 months)</h2>'
            "<ul class=\"md-body-md\"><li>Engine oil and filter</li><li>Air filter inspection</li>"
            "<li>Brake cleaning</li><li>AC filter clean</li><li>Fluid top-ups and wash</li></ul>"
            '<h2 class="md-headline-md mt-4 mb-3">Complete / major service (40,000 km+ intervals)</h2>'
            "<ul class=\"md-body-md\"><li>Everything in periodic, plus</li>"
            "<li>Spark plugs, belts, coolant flush (as per schedule)</li>"
            "<li>Deeper brake and suspension inspection</li>"
            "<li>Transmission fluid check where applicable</li></ul>"
            '<p class="md-body-md mt-3">YMO recommends the right package based on your odometer. '
            '<a href="/services/complete-car-servicing-in-pune">Book complete car servicing in Pune</a></p></div>',
        ),
        (
            "blog/doorstep-car-service-pune-worth-it",
            "Doorstep Car Service in Pune: Is It Worth It? | YMO",
            "Doorstep car service in Pune - compare mechanic-at-home vs pick-up/workshop model. When YMO full-service approach saves money and quality.",
            "Doorstep car service in Pune: is it worth it?",
            "Basic doorstep checks suit minor jobs; YMO pick-up + workshop model is worth it for AC, denting, brakes, and complete servicing from ₹1,999.",
            '<div class="ymo-content-section mb-5"><p class="md-body-md">Doorstep car services promise convenience - a mechanic visits your home. But convenience alone does not fix every problem. Here is an honest comparison.</p>'
            '<h2 class="md-headline-md mt-4 mb-3">When doorstep works</h2>'
            "<ul class=\"md-body-md\"><li>Battery jump-start or minor fuse issues</li>"
            "<li>Basic oil top-up or visual inspection</li>"
            "<li>Emergency diagnosis before a workshop visit</li></ul>"
            '<h2 class="md-headline-md mt-4 mb-3">When you need a workshop (YMO model)</h2>'
            "<ul class=\"md-body-md\"><li>AC gas recharge and leak repair</li>"
            "<li>Denting, painting, and polishing</li>"
            "<li>Brake rotor work and alignment</li>"
            "<li>Engine diagnostics and luxury cars</li></ul>"
            '<p class="md-body-md mt-3">YMO gives you doorstep convenience via <strong>free pick-up</strong> - without sacrificing workshop equipment. Read '
            '<a href="/why-choose-ymo">why choose YMO</a>.</p></div>',
        ),
    ]
    for b in blogs:
        pages.append(blog_post(*b))

    comp_body = (
        '<div class="ymo-content-section mb-5"><p class="md-body-md">Searching for doorstep car service in Pune often means choosing between a mechanic who visits your home and a service that collects your car for proper workshop repair. Here is a factual comparison of what YMO offers versus a typical mechanic-at-home model.</p></div>'
        '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Pick-up + workshop vs mechanic at home</h2>'
        '<div class="table-responsive"><table class="table md-body-md"><thead><tr><th>Capability</th><th>YMO (pick-up + workshop)</th><th>Mechanic at home</th></tr></thead><tbody>'
        "<tr><td>Periodic service from ₹1,999</td><td>Yes</td><td>Sometimes (limited)</td></tr>"
        "<tr><td>Free pick-up and drop</td><td>Yes</td><td>N/A (they come to you)</td></tr>"
        "<tr><td>AC gas recharge &amp; leak repair</td><td>Full workshop equipment</td><td>Portable kit only</td></tr>"
        "<tr><td>Denting &amp; painting</td><td>Yes - booth and colour match</td><td>Not possible at home</td></tr>"
        "<tr><td>3-stage polishing</td><td>Yes</td><td>No</td></tr>"
        "<tr><td>Luxury cars (BMW, Audi, Mercedes)</td><td>Yes - see luxury service</td><td>Rarely</td></tr>"
        "<tr><td>Upfront estimate before work</td><td>Yes</td><td>Varies</td></tr>"
        "<tr><td>WhatsApp photo updates</td><td>Yes</td><td>Varies</td></tr>"
        "</tbody></table></div></div>"
        '<div class="ymo-content-section mb-5"><h2 class="md-headline-md mb-3">Why workshop capability matters</h2>'
        "<p class=\"md-body-md mb-3\">A mechanic at your gate can handle oil changes and basic checks. But AC systems need leak testers, body work needs paint booths, and brake jobs often need lifts and torque tools. YMO's model: we pick up your car, do the job properly, and return it - you get convenience without compromising on equipment.</p>"
        '<p class="md-body-md mb-0">Explore <a href="/premium-luxury-car-service-pune">luxury car service in Pune</a>, '
        '<a href="/services">all services</a>, or <a href="/brands">brand-specific pages</a>.</p></div>'
    )
    pages.append(
        f"""    'why-choose-ymo' => array(
        'title'            => 'Why Choose YMO Over Doorstep Mechanics | Your Mechanic Online',
        'meta_description' => 'Compare YMO pick-up + workshop car service vs mechanic-at-home. AC, denting, polishing, luxury cars - full capability with free pick-up in Pune, Indore, Nashik.',
        'h1'               => 'Why choose YMO?',
        'intro'            => 'Workshop-grade car care with the convenience of free pick-up - an honest comparison.',
        'quick_answer'     => 'YMO combines free pick-up with full workshop services - AC repair, denting, polishing, and luxury car care - unlike basic mechanic-at-home visits.',
        'body'             => {php_str(comp_body)},
        'page_type'        => 'minimal',
        'updated_at'       => '{TODAY}',
        'faq'              => array(
            array('q' => 'What is the difference between YMO and a doorstep mechanic?', 'a' => 'A doorstep mechanic typically performs basic checks at your location. YMO picks up your car and services it at a workshop with proper equipment for AC, denting, painting, and diagnostics - then delivers it back.'),
            array('q' => 'Does YMO still offer doorstep convenience?', 'a' => 'Yes. We provide free pick-up and drop so you do not visit a garage or wait on-site - you get convenience plus workshop capability.'),
            array('q' => 'Can YMO service luxury cars?', 'a' => 'Yes. We offer premium and luxury car servicing including Mercedes, BMW, and Audi in Pune - see our luxury service page for details.'),
        ),
        'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
        'view'             => 'marketing/page',
    ),"""
    )

    header = """<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SEO growth pages - neighbourhoods, service×area, blog posts, comparison.
 * Regenerate: python deploy/marketing/scripts/generate_seo_growth_pages.py
 */
return array(
"""
    footer = "\n);\n"
    OUT.write_text(header + "\n".join(pages) + footer, encoding="utf-8")
    print(f"Wrote {OUT} with {len(pages)} pages")


if __name__ == "__main__":
    main()
