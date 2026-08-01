<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends Marketing_Controller
{
    public function index()
    {
        $this->page_meta = array(
            'title'            => 'Car Servicing in Pune, Indore & Nashik',
            'meta_title'       => 'Car Servicing in Pune, Indore & Nashik - Your Mechanic Online',
            'meta_description' => 'Expert car servicing and repair in Pune, Indore, and Nashik. Affordable rates, free doorstep pick-up, transparent pricing. Book online with YMO.',
            'h1'               => 'Car servicing in Pune, Indore & Nashik',
            'canonical_path'   => '',
            'quick_answer'     => 'Your Mechanic Online offers doorstep car servicing across Pune, Indore, and Nashik with free pick-up and drop, trained mechanics, and transparent pricing from ₹1999.',
            'og_image'         => '/assets/img/marketing/revslider/main/image_01.jpg',
        );
        $this->render_marketing('marketing/home', array(
            'booking_url'   => ymo_booking_url('packages'),
            'services'      => marketing_home_featured_services(),
            'city_strip'    => marketing_home_city_strip(),
            'brand_cards'   => marketing_home_brand_cards(),
            'benefits'      => $this->benefits(),
            'city_hint'     => marketing_city_hint_banner(),
        ));
    }

    /** @return array<int, array{title:string,body:string,icon:string}> */
    private function benefits()
    {
        return array(
            array(
                'title' => 'Free doorstep pick-up & delivery',
                'body'  => 'Skip the garage queue - we collect your car, service it, and return it when done.',
                'icon'  => 'local_shipping',
            ),
            array(
                'title' => 'Transparent, competitive pricing',
                'body'  => 'Upfront estimates for hundreds of jobs. No surprise add-ons at billing time.',
                'icon'  => 'payments',
            ),
            array(
                'title' => 'Expert technicians',
                'body'  => 'Trained mechanics, modern equipment, and updates on WhatsApp with photos.',
                'icon'  => 'engineering',
            ),
        );
    }
}
