<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unique copy per locality (landmarks, pick-up context) — reduces template similarity in SEO reports.
 * Keys match marketing_cities.php locality array keys (baner, wakad, …).
 */
return array(
    'pune' => array(
        'baner' => array(
            'landmarks'     => 'Baner Road, Balewadi High Street, and Orchid Avenue societies',
            'drive_context' => 'Pick-up from Baner usually reaches our workshop in 25–40 minutes via Aundh–Baner Road (longer in evening IT traffic toward Hinjewadi).',
            'local_jobs'    => 'Frequent pre-monsoon AC checks, clutch wear on Baner hill starts, and bumper touch-ups from mall parking at Phoenix Marketcity (Wakad side).',
        ),
        'wakad' => array(
            'landmarks'     => 'Datta Mandir Chowk, Wakad Bridge, and Hinjawadi–Wakad Link Road offices',
            'drive_context' => 'Wakad pick-ups often route via Mumbai–Bangalore Highway service lanes; allow 30–45 minutes in peak hours.',
            'local_jobs'    => 'Daily-driver periodic services for IT commuters, brake jobs after expressway runs, and interior cleaning after monsoon mud.',
        ),
        'hinjewadi' => array(
            'landmarks'     => 'Rajiv Gandhi Infotech Park phases, Marunji Road, and Hinjewadi Gaon',
            'drive_context' => 'We collect from tech-park gates and residential towers; off-peak workshop run is typically 20–35 minutes.',
            'local_jobs'    => 'Weekend slot demand from shift workers, AC performance checks before summer, and quick oil changes between project deadlines.',
        ),
        'aundh' => array(
            'landmarks'     => 'Aundh Gaon, ITI Road, and Parihar Chowk',
            'drive_context' => 'Aundh to workshop is often 20–30 minutes via University Road or Senapati Bapat Road.',
            'local_jobs'    => 'Family hatchback servicing, school-run brake inspections, and denting from tight society parking.',
        ),
        'bavdhan' => array(
            'landmarks'     => 'Bavdhan Budruk, NDA Road, and Paud Road junction',
            'drive_context' => 'Hilly lanes mean we confirm a pin before dispatch; average transit 25–40 minutes.',
            'local_jobs'    => 'SUV suspension checks on slope-heavy routes, monsoon underbody rust prevention, and polishing after tree sap exposure.',
        ),
        'viman_nagar' => array(
            'landmarks'     => 'Phoenix Marketcity, Clover Park, and Nagar Road flyover corridor',
            'drive_context' => 'Airport-side pick-ups: 25–35 minutes to workshop outside rush hour.',
            'local_jobs'    => 'Fleet-style servicing for rental cars, AC gas top-ups, and cosmetic dent repair from airport parking bumps.',
        ),
        'kharadi' => array(
            'landmarks'     => 'EON IT Park, World Trade Center, and Kharadi–Manjri Road',
            'drive_context' => 'Kharadi collections often align with late-morning slots after peak Nagar Road traffic.',
            'local_jobs'    => 'Creta/Nexon periodic packages, clutch care in stop-go IT traffic, and ceramic-coat enquiries from new towers.',
        ),
        'koregaon_park' => array(
            'landmarks'     => 'North Main Road, Lane 5, and Osho Garden vicinity',
            'drive_context' => 'One-way lanes — we call on arrival; typical workshop transit 25–40 minutes.',
            'local_jobs'    => 'Premium sedans, interior detailing before events, and low-speed parking dent work.',
        ),
        'kothrud' => array(
            'landmarks'     => 'Karve Road, Paud Phata, and Mayur Colony',
            'drive_context' => 'Central Pune access keeps pick-up-to-workshop time around 20–35 minutes.',
            'local_jobs'    => 'Student-family Maruti/Hyundai servicing, brake pad replacements, and battery checks before university breaks.',
        ),
        'hadapsar' => array(
            'landmarks'     => 'Magarpatta approach, Saswad Road, and Solapur Highway junction',
            'drive_context' => 'Eastern corridor jobs may add 15 minutes when Solapur Road is congested.',
            'local_jobs'    => 'Commercial vehicle light servicing, long-trip pre-service checks, and AC work for highway commuters.',
        ),
        'magarpatta' => array(
            'landmarks'     => 'Magarpatta City gates, Aditi Garden, and Hadapsar bypass',
            'drive_context' => 'Gate-pass friendly pick-up; workshop run usually 25–40 minutes.',
            'local_jobs'    => 'Corporate fleet maintenance slots, same-day periodic service, and tyre alignment after speed-breaker wear.',
        ),
        'wagholi' => array(
            'landmarks'     => 'Wagholi–Lonikand Road, Alfa Laval vicinity, and Nagar Road outbound',
            'drive_context' => 'Growing suburb — we confirm landmarks near bakeries and schools; transit often 30–50 minutes.',
            'local_jobs'    => 'New-owner first service, dust-heavy filter changes, and suspension checks on unfinished road stretches.',
        ),
        'pimple_saudagar' => array(
            'landmarks'     => 'Kalewadi Phata, Shakti Chowk, and Vision One Mall area',
            'drive_context' => 'Link to Aundh–Baner spine; off-peak workshop time about 25–35 minutes.',
            'local_jobs'    => 'Dual-car household packages, AC gas before summer, and rubbing/polish before resale.',
        ),
        'kalyani_nagar' => array(
            'landmarks'     => 'East Avenue, Nagar Road, and Aga Khan Palace side streets',
            'drive_context' => 'Nagar Road traffic sets the window; we text ETA after leaving workshop.',
            'local_jobs'    => 'Executive sedan servicing, pre-trip inspections, and insurance-claim dent assessments.',
        ),
        'warje' => array(
            'landmarks'     => 'Warje Malwadi, Karvenagar bridge, and Sinhagad Road approach',
            'drive_context' => 'Western Pune pick-ups via Karve Nagar — typically 25–40 minutes.',
            'local_jobs'    => 'Hill-start clutch wear, monsoon brake checks, and engine diagnostics on high-mileage city taxis.',
        ),
    ),
    'indore' => array(
        'vijay_nagar' => array(
            'landmarks'     => 'C21 Mall, Scheme 54 bridge, and MR-10 Road',
            'drive_context' => 'Central Indore pick-ups; workshop transit often 20–35 minutes.',
            'local_jobs'    => 'Compact car periodic service, AC gas in dry heat, and brake work after AB Road traffic.',
        ),
        'palasia' => array(
            'landmarks'     => 'Palasia Square, Greater Kailash Road, and Regal Circle approach',
            'drive_context' => 'Dense retail area — we meet near recognised shops; allow extra 10 minutes for parking handover.',
            'local_jobs'    => 'Older Honda City/Tata Indigo maintenance, denting from street parking, and battery replacement in heat.',
        ),
        'bhawarkua' => array(
            'landmarks'     => 'Bhawarkua Square, Rau Ring Road junction, and nearby colleges',
            'drive_context' => 'Student-area collections after 10 AM avoid lecture rush; transit 25–40 minutes.',
            'local_jobs'    => 'Budget periodic packages, clutch repairs on learner-driven cars, and tyre checks.',
        ),
        'ab_road' => array(
            'landmarks'     => 'Industry House stretch, LIG Square, and Palasia-link corridor',
            'drive_context' => 'AB Road flyover traffic drives scheduling — we confirm side-street pick-up points.',
            'local_jobs'    => 'Fleet SUVs, highway-trip servicing, and suspension work on expansion-joint wear.',
        ),
        'nipania' => array(
            'landmarks'     => 'Nipania bypass, Super Corridor, and Silicon City flats',
            'drive_context' => 'Newer layouts with clear pins; 25–45 minutes to workshop depending on Super Corridor flow.',
            'local_jobs'    => 'New car first service, ceramic and interior enquiries, and Creta/Seltos periodic plans.',
        ),
        'rau' => array(
            'landmarks'     => 'Rau Pithampur Road, IIM Indore vicinity, and Pithampur commuter belts',
            'drive_context' => 'Industrial corridor timing — early-morning pick-ups work best.',
            'local_jobs'    => 'High-mileage diesel servicing, brake overhauls, and pre-trip checks for Pithampur shifts.',
        ),
        'scheme_54' => array(
            'landmarks'     => 'Scheme 54 main, Press Complex, and Vijay Nagar connector',
            'drive_context' => 'Residential grid — typical transit 20–30 minutes.',
            'local_jobs'    => 'Family car packages, AC filter swaps in pollen season, and polish before weddings.',
        ),
        'old_palasia' => array(
            'landmarks'     => 'Old Palasia chowrasia, Kanadia Road shops, and heritage lanes',
            'drive_context' => 'Narrow lanes — driver calls on arrival; handover at main road corners.',
            'local_jobs'    => 'Legacy Maruti Zen/Alto upkeep, radiator checks, and paint touch-ups.',
        ),
        'sudama_nagar' => array(
            'landmarks'     => 'Sudama Nagar extension, Khajrana side, and ring-road access',
            'drive_context' => 'Southern Indore — workshop run about 25–40 minutes.',
            'local_jobs'    => 'Daily commuter servicing, monsoon wiper and brake checks, and exhaust inspections.',
        ),
        'mahalaxmi_nagar' => array(
            'landmarks'     => 'Mahalaxmi Nagar gate, Aerodrome Road, and adjacent townships',
            'drive_context' => 'Eastern townships; we use Aerodrome Road when open to save time.',
            'local_jobs'    => 'SUV periodic service, alignment after speed bumps, and insurance survey support.',
        ),
    ),
    'nashik' => array(
        'gangapur_road' => array(
            'landmarks'     => 'Gangapur Road wine-country stretch, College Road connector, and MICO circle',
            'drive_context' => 'West Nashik pick-ups — 20–35 minutes off-peak.',
            'local_jobs'    => 'Outstation-trip servicing, AC checks before grape-harvest season travel, and brake fluid updates.',
        ),
        'college_road' => array(
            'landmarks'     => 'College Road cafés, Canada Corner approach, and main shopping strip',
            'drive_context' => 'Evening foot-traffic areas — midday pick-ups preferred.',
            'local_jobs'    => 'Youth-owned hatchbacks, cosmetic dent repair, and interior odour treatment.',
        ),
        'panchavati' => array(
            'landmarks'     => 'Panchavati Karanja, Godavari ghat roads, and old city lanes',
            'drive_context' => 'Temple-area traffic on festival days — we reschedule if needed.',
            'local_jobs'    => 'Older sedan maintenance, cooling system checks, and battery tests in humid river-side air.',
        ),
        'indira_nagar' => array(
            'landmarks'     => 'Indira Nagar Nashik, Ashwin Nagar link, and CIDCO border',
            'drive_context' => 'Residential blocks with society gates; transit 25–40 minutes.',
            'local_jobs'    => 'Dual-car families, school-van brake inspections, and periodic Maruti Swift packages.',
        ),
        'nashik_road' => array(
            'landmarks'     => 'Nashik Road station, BYK College area, and Mumbai–Agra Highway frontage',
            'drive_context' => 'Highway-side pick-ups — confirm lane side for safe handover.',
            'local_jobs'    => 'Inter-city commuter servicing, clutch on highway taxis, and tyre rotation.',
        ),
        'cidco' => array(
            'landmarks'     => 'CIDCO Satpur, Ambad link, and industrial estate gates',
            'drive_context' => 'Industrial timing aligns with shift changes for smoother collection.',
            'local_jobs'    => 'Commercial light vehicles, diesel filter service, and fleet brake jobs.',
        ),
        'deolali' => array(
            'landmarks'     => 'Deolali Camp, Artillery Centre road, and Igatpuri approach',
            'drive_context' => 'Cantt area rules respected — pick-up at main gate only.',
            'local_jobs'    => 'Stable-family car care, long-storage battery maintenance, and pre-monsoon checks.',
        ),
        'mumbai_naka' => array(
            'landmarks'     => 'Mumbai Naka circle, Pimpalgaon Baswant road split, and truck-route frontage',
            'drive_context' => 'Heavy-vehicle corridor — we use side streets for safe loading.',
            'local_jobs'    => 'Suspension and steering checks, headlight alignment, and brake upgrades.',
        ),
        'govind_nagar' => array(
            'landmarks'     => 'Govind Nagar Nashik, Dwarka link, and Panchavati bypass',
            'drive_context' => 'Central-west Nashik — about 20–35 minutes to workshop.',
            'local_jobs'    => 'Routine periodic service, AC gas, and denting from market parking.',
        ),
        'dwarka' => array(
            'landmarks'     => 'Dwarka Nashik township, Nashik Road bypass, and new builds',
            'drive_context' => 'Wide roads help quick collection; 25–40 minute workshop transit.',
            'local_jobs'    => 'New tower residents, first free service follow-ups, and ceramic-coat quotes.',
        ),
        'uday_nagar' => array(
            'landmarks'     => 'Uday Nagar main, Gangapur–Adgaon connector, and college belt',
            'drive_context' => 'Student rentals common — ID verified at handover only when required by society.',
            'local_jobs'    => 'Budget servicing, tyre changes, and pre-lease return inspections.',
        ),
    ),
);
