<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Seo extends Marketing_Controller
{
    public function sitemap()
    {
        $this->output->set_content_type('application/xml', 'utf-8');

        $urls = array(
            array(
                'loc'        => marketing_canonical_url(''),
                'priority'   => '1.0',
                'changefreq' => 'weekly',
                'lastmod'    => '2026-08-27',
            ),
            array(
                'loc'        => marketing_canonical_url('contact-us'),
                'priority'   => '0.8',
                'changefreq' => 'monthly',
                'lastmod'    => '2026-07-20',
            ),
        );
        foreach (marketing_sitemap_pages() as $path => $page) {
            if (!is_array($page)) {
                continue;
            }
            $lastmod = !empty($page['updated_at']) ? $page['updated_at'] : '2026-08-01';
            $urls[] = array(
                'loc'        => marketing_canonical_url($path),
                'priority'   => marketing_page_priority($page),
                'changefreq' => marketing_page_changefreq($page),
                'lastmod'    => $lastmod,
            );
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url>';
            $xml .= '<loc>'.htmlspecialchars($u['loc'], ENT_XML1).'</loc>';
            if (!empty($u['lastmod'])) {
                $xml .= '<lastmod>'.htmlspecialchars($u['lastmod'], ENT_XML1).'</lastmod>';
            }
            if (!empty($u['changefreq'])) {
                $xml .= '<changefreq>'.htmlspecialchars($u['changefreq'], ENT_XML1).'</changefreq>';
            }
            $xml .= '<priority>'.htmlspecialchars($u['priority'], ENT_XML1).'</priority>';
            $xml .= '</url>'."\n";
        }
        $xml .= '</urlset>';
        $this->output->set_output($xml);
    }

    public function robots()
    {
        $lines = array(
            'User-agent: *',
            'Allow: /',
            'Sitemap: '.marketing_canonical_url('sitemap.xml'),
        );
        $this->output
            ->set_content_type('text/plain', 'utf-8')
            ->set_output(implode("\n", $lines)."\n");
    }

    public function llms()
    {
        $this->output
            ->set_content_type('text/plain', 'utf-8')
            ->set_output(marketing_llms_txt());
    }
}
