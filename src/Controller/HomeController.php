<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;

class HomeController extends Controller
{
  public function index()
  {
    // Create a new Guzzle Plain Client
    $client = $this->getGuzzleFileCachedClient();

    $requestURL = "https://api.nasa.gov/planetary/apod?api_key=NNKOjkoul8n1CH18TWA9gwngW1s1SmjESPjNoUFo";

    $res = $client->request('GET', $requestURL);

    $obj = json_decode($res->getBody());

      return $this->render('home/home.html.twig', array('title' => $obj->title,
      										'explanation' => $obj->explanation,
      										'image' => $obj->url));
  }

  /**
     * Returns a GuzzleClient that uses a cache manager, so you will use the API without any problem and
     * request many times as you want.
     *
     * The cache last 10 minutes as recommended in the API.
     */
    private function getGuzzleFileCachedClient(){
        // Create a HandlerStack
        $stack = HandlerStack::create();

        // 10 minutes to keep the cache
        $TTL = 600;

        // Retrieve the cache folder path of your Symfony Project
        $cacheFolderPath = $this->get('kernel')->getRootDir() . '/../var/cache';

        // Instantiate the cache storage: a PSR-6 file system cache with
        // a default lifetime of 10 minutes (60 seconds).
        $cache_storage = new Psr6CacheStorage(
            new FilesystemAdapter(
                // Create Folder GuzzleFileCache inside the providen cache folder path
                'GuzzleFileCache',
                $TTL,
                $cacheFolderPath
            )
        );

        // Add Cache Method
        $stack->push(
            new CacheMiddleware(
                new GreedyCacheStrategy(
                    $cache_storage,
                    600 // the TTL in seconds
                )
            ),
            'greedy-cache'
        );

        // Initialize the client with the handler option and return it
        return new Client(['handler' => $stack]);
    }

}

?>
