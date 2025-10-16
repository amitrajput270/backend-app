<?php

namespace App\Http\Controllers;

use Feeds;
use Illuminate\Support\Facades\Log;

use App\Traits\TestTraits;


class RssFeedController extends Controller
{
    use TestTraits;

    public function index()
    {
        try {

            $url = "https://www.shine.com/job-search/laravel-codeigniter-jobs-in-moradabad-noida-ghaziabad-haridwar-delhi-dehradun-agra-aligarh-bareilly-3?q=laravel-codeigniter&qActual=Laravel%20Codeigniter,%20&loc=Moradabad,%20Noida,%20Ghaziabad,%20Haridwar,%20Delhi,%20Dehradun,%20Agra,%20Aligarh,%20Bareilly&minexp=9";
            // Example RSS feed URL
            $feed = Feeds::make($url, true); // true = force cache refresh
            dd($feed);
            // Check if feed is valid
            if (!$feed) {
                throw new \Exception('Failed to parse RSS feed');
            }

            // Process feed items to extract readable data
            $items = [];
            $feedItems = $feed->get_items();

            // Add debugging
            foreach ($feedItems as $index => $item) {
                if ($index >= 100) break; // Limit to 20 items

                $items[] = [
                    'title' => $item->get_title() ?: 'No Title',
                    'link' => $item->get_permalink() ?: '#',
                    'description' => strip_tags($item->get_description()) ?: 'No description available',
                    'date' => $item->get_date('Y-m-d H:i:s') ?: date('Y-m-d H:i:s'),
                    'author' => $item->get_author() ? $item->get_author()->get_name() : 'Unknown',
                ];
            }

            $data = [
                'title' => $feed->get_title() ?: 'RSS Feed',
                'permalink' => $feed->get_permalink() ?: '',
                'description' => $feed->get_description() ?: 'RSS Feed Description',
                'items' => $items,
                'slot' => '',
                'itemCount' => count($items), // Add item count for debugging
            ];

            // Add temporary debug output
            if (request()->has('debug')) {
                dd($data); // This will dump the data for debugging
            }

            return view('rss.index', $data);
        } catch (\Exception $e) {
            Log::error('RSS Feed Error: ' . $e->getMessage());

            // Add temporary debug output for errors
            if (request()->has('debug')) {
                dd('Error: ' . $e->getMessage(), $e->getTrace());
            }

            return view('rss.index', [
                'title' => 'RSS Feed Error',
                'error' => 'Could not load RSS feed: ' . $e->getMessage(),
                'items' => [],
                'slot' => '',
                'itemCount' => 0,
            ]);
        }
    }


    public function traitTest()
    {

        return $this->sayHello();
    }
}
