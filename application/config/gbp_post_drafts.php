<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Ready-made Google post drafts, loaded into the CRM with:
 *   php index.php cli/gbp_drafts load
 * They are saved as drafts (never auto-posted); publish each one from
 * Google posts in the CRM. Facts come from the Hinjewadi page
 * (marketing_pages_data.php): keep prices and hours in step with it.
 * No phone numbers in the text: Google rejects them (use the CALL button).
 * photo: a website image (served from www), JPG, landscape.
 */
$P = 'assets/img/workshops/hinjewadi/gbp/';

$config['gbp_post_drafts'] = array(

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-lifts-and-bays.jpg', 'summary' =>
"Take a look inside our Hinjewadi workshop. We're inside Rajiv Gandhi Infotech Park, Phase 1, with two-post lifts, a wide service floor and a team that works on all car brands, from Maruti, Hyundai and Tata to BMW, Mercedes-Benz and Audi.

Drive in, or book a free pick-up from your office gate or society anywhere in Hinjewadi Phase 1, 2 or 3. You approve the estimate before any work starts, and we send WhatsApp photos and videos as the job happens.

Open every day except Thursday, 9 AM to 8 PM (Sunday from 8:30 AM)."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-two-post-lifts.jpg', 'summary' =>
"Due for a service? Complete car servicing at our Hinjewadi workshop starts from ₹2,499 (SUVs from ₹2,999).

It covers an oil and filter change, brake cleaning, AC filter, coolant top-up and an interior vacuum. You see the full estimate before we start, and nothing extra is done without your OK.

Most services are finished the same day: hand over the car in the morning, get it back after work."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-workshop-floor-wide.jpg', 'summary' =>
"Working in Hinjewadi and no time to visit a garage? We pick up your car for free from your office gate or housing society across Phase 1, 2 and 3, including the Infosys and Wipro campuses in Phase 2 and the TCS, Tech Mahindra and Megapolis area in Phase 3.

Our driver calls before arriving, we service the car at our Phase 1 workshop and, for most jobs, drop it back the same evening. You follow the work on WhatsApp from your desk."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-two-post-lifts.jpg', 'summary' =>
"Planning a road trip over Christmas or New Year? Get your brakes checked first.

At our Hinjewadi workshop, brake pads start from ₹499, and a full brake inspection with fluid top-up from ₹899. If you hear squealing, the pedal feels soft or the car pulls to one side when you brake, don't wait for the trip.

We show you the worn parts on WhatsApp before replacing anything."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-service-bays.jpg', 'summary' =>
"Weak cooling, a musty smell, or the AC taking too long to cool the cabin? Car AC service at our Hinjewadi workshop starts from ₹999.

We check and recharge the gas, run a leak test and inspect the compressor, so you know exactly what's wrong before paying for any repair. Pick-up is free across Hinjewadi Phase 1, 2 and 3."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-lifts-and-bays.jpg', 'summary' =>
"Only need an oil change? Our express oil and filter change in Hinjewadi starts from ₹1,499.

Fresh oil protects the engine, especially in the stop-and-go traffic around the IT park. Drive in to our Phase 1 workshop or book a free pick-up. If we spot anything else that needs attention, we send you photos and ask first."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-two-post-lifts.jpg', 'summary' =>
"You shouldn't have to guess what happened to your car at the garage.

When your car is at our Hinjewadi workshop, we send WhatsApp photos and videos at every stage of the work. If we find something extra, like worn brake pads or a leaking hose, we show it to you first. You approve it, or we leave it.

For most bookings you pay after the car is delivered back, once you've seen the work."),

    array('workshops' => 'hinjewadi', 'cta' => 'CALL', 'photo' => $P.'hinjewadi-workshop-floor-wide.jpg', 'summary' =>
"Weekdays too busy? Our Hinjewadi workshop is open on Sundays from 8:30 AM.

We're open every day except Thursday, 9 AM to 8 PM, and from 8:30 AM on Sundays. Bring your car in over the weekend or book a free pick-up from your society in Hinjewadi Phase 1, 2 or 3. Most services are ready the same day."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-lifts-and-bays.jpg', 'summary' =>
"From a Maruti Swift to a Mercedes-Benz, we service all brands at our Hinjewadi workshop: Maruti, Hyundai, Tata, Honda, Toyota, Kia, Mahindra, Skoda, Volkswagen, BMW, Mercedes-Benz and Audi.

You get an upfront estimate before any work begins, WhatsApp photo updates while it's done and, for most bookings, you pay after delivery."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-service-bays.jpg', 'summary' =>
"A scratch or dent from a tight parking spot? Denting and painting at our Hinjewadi workshop starts from ₹4,500 per panel, with colour matching.

Bodywork takes longer than a regular service, so we give you the price and the timeline upfront. Pick-up and drop are free across Hinjewadi Phase 1, 2 and 3."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-workshop-floor-wide.jpg', 'summary' =>
"Get your car looking its best for the New Year. 3-stage rubbing and polishing at our Hinjewadi workshop starts from ₹6,500.

It brings back the gloss and takes out light swirl marks and dull patches in the paint. Book a pick-up from your office or society in Hinjewadi, Wakad or Baner, and we'll bring the car back after the job."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-two-post-lifts.jpg', 'summary' =>
"Daily commutes, dust and long drives take a toll on your car's cabin. Interior deep cleaning at our Hinjewadi workshop starts from ₹2,500.

Book it on its own or add it to your next service. We pick the car up for free from anywhere in Hinjewadi Phase 1, 2 or 3 and bring it back clean."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-lifts-and-bays.jpg', 'summary' =>
"Check-engine light on? Don't ignore it. An engine diagnostic scan at our Hinjewadi workshop starts from ₹999.

We read the fault codes, explain what they mean in plain words and give you the price of the repair before doing anything. Drive in to our Phase 1 workshop or book a free pick-up."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-service-bays.jpg', 'summary' =>
"Hand over your car in the morning, get it back after work.

For most periodic services and small repairs, our Hinjewadi team picks up your car from your office gate in Phase 1, 2 or 3 and drops it back the same evening. Bigger jobs such as denting and painting take longer, and we tell you the timeline upfront."),

    array('workshops' => 'hinjewadi', 'cta' => 'BOOK', 'photo' => $P.'hinjewadi-workshop-floor-wide.jpg', 'summary' =>
"Hinjewadi or Hinjawadi, we're right inside Rajiv Gandhi Infotech Park, Phase 1.

Besides the IT park, we pick up cars from Marunji, Maan, Kasarsai Road, Wakad, Punawale, Tathawade, Ravet, Baner, Balewadi, Sus and Pashan, and bring them back after the job. Complete car servicing starts from ₹2,499, with WhatsApp photo updates at every stage."),
);
