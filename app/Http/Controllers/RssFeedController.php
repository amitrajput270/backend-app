<?php

namespace App\Http\Controllers;

use Feeds;
use Illuminate\Support\Facades\Log;

class RssFeedController extends Controller
{
    public function index()
    {
        try {
            // Example RSS feed URL
            $feed = Feeds::make('https://www.linkedin.com/jobs', true); // true = force cache refresh

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
}
