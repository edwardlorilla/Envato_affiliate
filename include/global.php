<?php
/**
 * Envato Affiliate Money Maker - http://codecanyon.net/item/envato-affiliate-money-maker/6981745?ref=preciouscoder
 *
 * @author      preciouscoder
 * @copyright   preciouscoder
 */

/**
 * Get feed file title
 *
 * @param string $url Url of feed file
 *
 * @return string
 */
function getFeedTitle($url = null)
    {
    $app = \Slim\Slim::getInstance();
    $app->simplepie->set_feed_url($url);
    $app->simplepie->set_stupidly_fast(true);
    $app->simplepie->set_timeout(30);
    $app->simplepie->init();
    $app->simplepie->handle_content_type();

    return $app->simplepie->get_title();
    }
/**
 * Get feed file items
 *
 * @param array $source Source feeds data
 *
 * @return array
 */
function getFeedItems($source = array())
    {
    $app = \Slim\Slim::getInstance();
    $app->simplepie->set_feed_url($source['url']);
    $app->simplepie->set_stupidly_fast(true);
    $app->simplepie->set_timeout(30);
    $app->simplepie->init();
    $app->simplepie->handle_content_type();
    $items = array();
    foreach ($app->simplepie->get_items() as $item) {
        $items[] = array(
            'id' => preg_replace('/^.*Item\/(\d+)$/i', '\1', $item->get_id()),
            'category_id' => $source['category_id'],
            'description' => $item->get_content()
        );
        }

    return $items;
    }
/**
 * Get items details from envato api
 *
 * @param integer $item_id Id of items on envato markets
 *
 * @return array
 */
function getEnvatoDetail($item_id = null)
    {
    if (!$item_id) {
        return array();
        }
    $app                       = \Slim\Slim::getInstance();
    $data                      = $app->envato->item_details($item_id);
    $item['slug']              = URLify::filter($data->item);
    $item['uploaded_on']       = date('Y-m-d H:i:s', strtotime($data->uploaded_on));
    $item['last_update']       = date('Y-m-d H:i:s', strtotime($data->last_update));
    $item['title']             = $data->item;
    $item['description']       = getItemDescription($data->url);
    $item['image']             = $data->live_preview_url;
    $item['url']               = $data->url;
    $item['demo']              = getItemDemoeUrl($data->url, $item_id);
    $item['preview']           = getItemPreviewUrl($data->url, $item_id);
    $item['preview_audio_url'] = @$data->preview_url;
    $item['preview_video_url'] = @$data->live_preview_video_url;
    $item['price']             = $data->cost;
    $item['thumbnail']         = $data->thumbnail;
    $item['rating']            = $data->rating_decimal;
    $item['sales']             = $data->sales;
    $item['user']              = $data->user;
    $item['tags']              = $data->tags;

    return $item;
    }
/**
 * Get pager information
 *
 * @param integer $page Current page number
 * @param integer $count Total numbers of records
 * @param string $route Route path for links
 * @param integer $length Length of each page records
 *
 * @return array
 */
function getPager($page = 1, $count = 0, $route = null, $length = 10)
    {
    $app                         = \Slim\Slim::getInstance();
    $page                        = $page ? $page : 1;
    $pager['query']              = $app->request->get('query') ? $app->request->get('query') : '';
    $pager['page']               = $app->request->get('page') ? $app->request->get('page') : 1;
    $pager['sort']               = $app->request->get('sort') ? $app->request->get('sort') : 'uploaded_on';
    $pager['direction']          = $app->request->get('direction') ? $app->request->get('direction') : 'desc';
    $pager['length']             = $length ? $length : 10;
    $pager['query']['sort']      = $pager['sort'];
    $pager['query']['direction'] = $pager['direction'];
    $pager['query']['page']      = ''; //$pager['page'];
    $pager['query']              = http_build_query($pager['query']);
    $pager['route']              = sprintf('%s?%s', $route, $pager['query']);
    $pager['total']              = $count;
    $pager['offset']             = $pager['length'] * ($pager['page'] - 1);
    $pager['order']              = sprintf('%s %s', $pager['sort'], $pager['direction']);
    $pager['pages']              = @ceil($pager['total'] / $pager['length']);
    $pager['first']              = 1;
    $pager['last']               = $pager['pages'];
    $pager['prev']               = $pager['page'] - 1;
    $pager['next']               = $pager['page'] + 1;
    $pager['adja']               = 1;

    return $pager;
    }
function getPagers($page = 1, $count = 0, $route = null, $length = 10)
    {
    $app                         = \Slim\Slim::getInstance();
    $page                        = $page ? $page : 1;
    $pager['query']              = $app->request->get('query') ? $app->request->get('query') : '';
    $pager['page']               = $app->request->get('page') ? $app->request->get('page') : 1;
    $pager['sort']               = $app->request->get('sort') ? $app->request->get('sort') : 'id';
    $pager['direction']          = $app->request->get('direction') ? $app->request->get('direction') : 'desc';
    $pager['length']             = $length ? $length : 10;
    $pager['query']['sort']      = $pager['sort'];
    $pager['query']['direction'] = $pager['direction'];
    $pager['query']['page']      = ''; //$pager['page'];
    $pager['query']              = http_build_query($pager['query']);
    $pager['route']              = sprintf('%s?%s', $route, $pager['query']);
    $pager['total']              = $count;
    $pager['offset']             = $pager['length'] * ($pager['page'] - 1);
    $pager['order']              = sprintf('%s %s', $pager['sort'], $pager['direction']);
    $pager['pages']              = @ceil($pager['total'] / $pager['length']);
    $pager['first']              = 1;
    $pager['last']               = $pager['pages'];
    $pager['prev']               = $pager['page'] - 1;
    $pager['next']               = $pager['page'] + 1;
    $pager['adja']               = 1;

    return $pager;
    }
function getPagerAuthor($page = 1, $count = 0, $route = null, $length = 10)
    {
    $app                         = \Slim\Slim::getInstance();
    $page                        = $page ? $page : 1;
    $pager['query']              = $app->request->get('query') ? $app->request->get('query') : '';
    $pager['page']               = $app->request->get('page') ? $app->request->get('page') : 1;
    $pager['sort']               = $app->request->get('sort') ? $app->request->get('sort') : 'username';
    $pager['direction']          = $app->request->get('direction') ? $app->request->get('direction') : 'asc';
    $pager['length']             = $length ? $length : 10;
    $pager['query']['sort']      = $pager['sort'];
    $pager['query']['direction'] = $pager['direction'];
    $pager['query']['page']      = ''; //$pager['page'];
    $pager['query']              = http_build_query($pager['query']);
    $pager['route']              = sprintf('%s?%s', $route, $pager['query']);
    $pager['total']              = $count;
    $pager['offset']             = $pager['length'] * ($pager['page'] - 1);
    $pager['order']              = sprintf('%s %s', $pager['sort'], $pager['direction']);
    $pager['pages']              = @ceil($pager['total'] / $pager['length']);
    $pager['first']              = 1;
    $pager['last']               = $pager['pages'];
    $pager['prev']               = $pager['page'] - 1;
    $pager['next']               = $pager['page'] + 1;
    $pager['adja']               = 1;

    return $pager;
    }
function getItemPager($count = 0, $route = null, $length = 10)
    {
    $app                         = \Slim\Slim::getInstance();
    $pager['page']               = $app->request->get('page') ? $app->request->get('page') : 1;
    $pager['sort']               = $app->request->get('sort') ? $app->request->get('sort') : 'uploaded_on';
    $pager['direction']          = $app->request->get('direction') ? $app->request->get('direction') : 'desc';
    $pager['length']             = $length ? $length : 10;
    $pager['query']['keyword']   = $app->request->get('keyword');
    $pager['query']['sort']      = $pager['sort'];
    $pager['query']['direction'] = $pager['direction'];
    $pager['query']['page']      = ''; //$pager['page'];
    $pager['query']              = http_build_query($pager['query']);
    $pager['route']              = sprintf('%s?%s', $route, $pager['query']);
    $pager['total']              = $count;
    $pager['offset']             = $pager['length'] * ($pager['page'] - 1);
    $pager['order']              = sprintf('%s %s', $pager['sort'], $pager['direction']);
    $pager['pages']              = @ceil($pager['total'] / $pager['length']);
    $pager['first']              = 1;
    $pager['last']               = $pager['pages'];
    $pager['prev']               = $pager['page'] - 1;
    $pager['next']               = $pager['page'] + 1;
    $pager['adja']               = 1;

    return $pager;
    }
/**
 * Get settings and return configs
 *
 * @return array
 */
function getConfigs()
    {
    $app       = \Slim\Slim::getInstance();
    $configs   = array();
    $settings  = \ORM::for_table('setting')->find_array();
    $stashItem = $app->stash->getItem('configs');
    $stashData = $stashItem->get();
    if ($stashItem->isMiss() == false) {
        return $stashData;
        }
    foreach ($settings as $setting) {
        $configs[$setting['name']] = $setting['value'];
        }
    $stashItem->set($configs, 60 * 60 * 24);

    return $configs;
    }
/**
 * Check if system already installed
 *
 * @return integer Return number of exist tables
 */
function getInstall()
    {
    if (!is_writable(realpath('cache'))) {
        die('Cache folder is not writable!');
        }
    if (version_compare(PHP_VERSION, '5.3.0') < 0) {
        die('PHP 5.3.0 or higher required.');
        }
    $pdo = new \PDO(sprintf('mysql:dbname=%s;host=%s', DB_NAME, DB_HOST), DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $result = $pdo->query("SHOW TABLES LIKE 'user'");

    return $result->rowCount();
    }
/**
 * Import and execute installation sqls
 *
 * @return void nothing
 */
function setInstall()
    {
    $app    = \Slim\Slim::getInstance();
    $query  = null;
    $folder = realpath('schema');
    $files  = scandir($folder);
    printf(nl2br("<h2>beginning of import files.</h2>\n"));
    foreach ($files as $file) {
        if (@end(explode('.', $file)) == 'sql') {
            printf(nl2br("Start import '%s'\n"), $file);
            $query = file_get_contents($folder . '/' . $file) . "\n";
            try {
                $pdo = new \PDO(sprintf('mysql:dbname=%s;host=%s', DB_NAME, DB_HOST), DB_USER, DB_PASS);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $pdo->exec($query);
                } catch (\PDOException $e) {
                $app->firephp->error($e);
                }
            print '<hr>';
            }
        }
    }
/**
 * Get url response status
 *
 * @param string $url Envato page urls
 *
 * @return integer
 */
function getUrlStatus($url)
    {
    $header = @get_headers($url);
    $status = array();
    @preg_match('/HTTP\/.* ([0-9]+) .*/', $header[0], $status);

    return $status[1];
    }
/**
 * Get items demo url if exist
 *
 * @param string $url Item detail page url
 * @param integer $item_id Item envato market id
 *
 * @return string      Item demo page url
 */
function getItemDemoeUrl($url, $item_id)
    {
    $url = str_replace($item_id, 'full_screen_preview/' . $item_id, $url);

    return (getUrlStatus($url) == 404) ? null : $url;
    }
/**
 * Get items preview url if exist
 *
 * @param string $url Item detail page url
 * @param integer $item_id Item envato market id
 *
 * @return string      Item preview page url
 */
function getItemPreviewUrl($url, $item_id)
    {
    $url = str_replace($item_id, 'screenshots/' . $item_id, $url);

    return (getUrlStatus($url) == 404) ? null : $url;
    }
function getItemDescription($url)
    {
    ini_set('user_agent', 'Mozilla/5.0');
    $html = \Sunra\PhpSimple\HtmlDomParser::file_get_html($url);
    if ($html) {
      $description = '';
    foreach ($html->find('div.user-html') as $e) {
            $description .= $e->innertext;
            }
        }

    return $description;
    }
/**
 * Get the list of categories
 *
 * @param integer $category_id Parent category id
 *
 * @return array
 */
function getCategories($category_id = 0)
    {
    $categories = \ORM::for_table('category')->select('category.*')->where_equal('category_id', $category_id)->find_array();

    return $categories ? $categories : array();
    }
/**
 * Set items data to the database
 *
 * @param array $data Item data that should be saved
 *
 * @return mixed
 */
function setItemData($data = array())
    {
    $app  = \Slim\Slim::getInstance();
    $item = \ORM::for_table('item')->find_one($data['id']);
    if ($item === false) {
        $item = \ORM::for_table('item')->create();
        }
    if ($item->get('static') != 0) {
        return false;
        }
    foreach ($data as $key => $value) {
        $item->set($key, $value);
        }
    try {
        return $item->save();
        } catch (Exception $e) {
        echo $e->getMessage();
        exit;
        $app->firephp->error($e);

        return false;
        }
    }
function setItemAuthor($user = null)
    {
    $app  = \Slim\Slim::getInstance();
    $data = $app->envato->public_user_data($user);
    try {
        $author = \ORM::for_table('author')->where('username', $user)->find_one();
        if ($author === false) {
            $author = \ORM::for_table('author')->create();
            }
        $author->set('username', $data->username);
        $author->set('country', $data->country);
        $author->set('sales', $data->sales);
        $author->set('location', $data->location);
        $author->set('image', $data->image);
        $author->set('followers', $data->followers);

        return $author->save();
        } catch (Exception $e) {
        $app->firephp->error($e);

        return false;
        }
    }
/**
 * Get categories list and return tree labeled
 *
 * @param array $datas Array of categories tree
 * @param integer $parent Parent category id
 *
 * @return array Array of nested categories
 */
function getCategoryTree($datas = array(), $parent = 0)
    {
    $datas = explode("\n", doGetCategoryTree($datas, $parent));
    $datas = array_filter($datas);
    foreach ($datas as $i => $data) {
        list($id, $category_id, $alias, $name, $depth) = explode('|', $data);
        unset($datas[$i]);
        $datas[$i]['id']          = $id;
        $datas[$i]['category_id'] = $category_id;
        $datas[$i]['alias']       = $alias;
        $datas[$i]['name']        = $name;
        $datas[$i]['depth']       = $depth;
        }

    return $datas;
    }
/**
 * Private function for generate category tree
 *
 * @param array $datas Array of categories
 * @param integer $parent Parent category id
 * @param integer $depth Depth of category tree
 *
 * @return array Array of nested categories
 */
function doGetCategoryTree($datas, $parent = 0, $depth = 0)
    {
    if ($depth > 1000) {
        return ''; // Make sure not to have an endless recursion
        }
    $tree = '';
    for ($i = 0, $ni = count($datas); $i < $ni; $i++) {
        if ($datas[$i]['category_id'] == $parent) {
            $tree .= "{$datas[$i]['id']}|";
            $tree .= "{$datas[$i]['category_id']}|";
            $tree .= "{$datas[$i]['alias']}|";
            $tree .= "{$datas[$i]['name']}|";
            $tree .= "{$depth}\n";
            $tree .= doGetCategoryTree($datas, $datas[$i]['id'], $depth + 1);
            }
        }

    return $tree;
    }
/**
 * Seperate and set tags for items
 *
 * @param integer $item Item primary id
 * @param string $tags Tags string seperated by camma
 *
 * @return void Nothing
 */
function setItemTags($item = null, $tags = null)
    {
    $app = \Slim\Slim::getInstance();
    if (!$item || !$tags) {
        return false;
        }
    $tags = explode(',', $tags);
    $item = \ORM::for_table('item')->find_one($item);
    foreach ($tags as $_tag) {
        $_tag = @strtolower(trim($_tag));
        if ($_tag) {
            $tag = \ORM::for_table('tag')->where_equal('name', $_tag)->find_one();
            if (!$tag) {
                $tag = \ORM::for_table('tag')->create();
                $tag->set('name', $_tag);
                $tag->save();
                }
            $item_tag = \ORM::for_table('item_tag')->create();
            $item_tag->set(array(
                'tag_id' => $tag->id,
                'item_id' => $item->id
            ));
            try {
                $item_tag->save();
                } catch (Exception $e) {
                $app->firephp->error($e);
                continue;
                }
            }
        }
    }
/**
 * Generate sitemap index and sitemaps
 *
 * @return mixed
 */
function genSitemapIndex()
    {
    $app     = \Slim\Slim::getInstance();
    $sitemap = new \SitemapPHP\Sitemap($app->request->getUrl() . $app->request->getRootUri());
    $sitemap->setPath(realpath('cache/sitemap') . '/');
    $sitemap->addItem('/', '1.0', 'daily', 'Today');
    $pages = \ORM::for_table('page')->order_by_desc('id')->find_array();
    foreach ($pages as $page) {
        $sitemap->addItem('/' . $page['alias'], '0.8', 'monthly', null);
        }
    $limit  = 100;
    $offset = 0;
    do {
        $items = \ORM::for_table('item')->limit($limit)->offset($offset)->order_by_desc('id')->find_array();
        foreach ($items as $item) {
            $sitemap->addItem('/item/' . $item['slug'] . '/' . $item['id'], '0.6', 'weekly', null);
            }
        $offset = $offset + $limit;
        } while (count($items) == $limit);
    $sitemap->createSitemapIndex($app->request->getUrl() . '/sitemap/', 'Today');
    }
/**
 * Generate navbar menu
 *
 * @return array()
 */
function getNewNavbar()
    {
    $app = \Slim\Slim::getInstance();
    try {
        $market = $app->router()->getCurrentRoute()->getParam('market');
        } catch (Exception $e) {
        $market = false;
        }
    $url    = function ($market, $category) use ($app) {
        return $app->urlFor('root', array(
            'market' => $market,
            'category' => $category
        ));
        };
    $enable = json_decode($app->config('configs.site_markets'), true);
    $html   = '<ul class="nav navbar-nav">';
    foreach (getCategories() as $i => $category) {
        if (!in_array($category['id'], $enable)) {
            continue;
            }
        if (!$market && !$i) {
            }
        $html .= '<li ' . ($category['alias'] == $market ? 'class="active dropdown"' : 'class="dropdown"') . '>';
        $childs = getCategories($category['id']);
        if (!empty($childs)) {
            $html .= '<a href="' . $url($category['alias'], 'all') . '" class="dropdown-toggle disabled" data-toggle="dropdown">' . $category['name'] . ' <b class="caret"></b></a><ul class="dropdown-menu">';
            foreach ($childs as $child) {
                $html .= '<li><a href="' . $url($category['alias'], $child['alias']) . '">' . $child['name'] . '</a></li>';
                }
            $html .= '</ul>';
            } else {
            $html .= '<a href="' . $url($category['alias'], 'all') . '">' . $category['name'] . '</a>';
            }
        $html .= '';
        }
    $html .= '</ul>';

    return array(
        'navbar' => $html
    );
    }
/**
 * Import and set market categories from envato websites
 *
 * @param array $market Market informations
 *
 * @return void
 */
function setMarketCategories($market = array())
    {
    $app  = \Slim\Slim::getInstance();
    $url  = sprintf("http://%s.net/category/list", $market['alias']);
    $html = \Sunra\PhpSimple\HtmlDomParser::file_get_html($url);
    foreach ($html->find('ul.category-tree') as $ul) {
        $name     = $ul->find('li a', 0)->plaintext;
        $alias    = @end(explode('/', $ul->find('li a', 0)->href));
        $category = \ORM::for_table('category')->where_equal('alias', $alias);
        if (!$category->find_one()) {
            $category = $category->create();
            } else {
            $category = $category->find_one();
            }
        $category->set('name', $name);
        $category->set('alias', $alias);
        $category->set('category_id', $market['id']);
        if ($category->save()) {
            for ($i = 1; $i < count($ul->find('li a')); $i++) {
                $category->create();
                $category->set(array(
                    'name' => $ul->find('li a', $i)->plaintext,
                    'alias' => @end(explode('/', $ul->find('li a', $i)->href)),
                    'category_id' => $category->id
                ));
                try {
                    $category->save();
                    } catch (Exception $e) {
                    $app->firephp->error($e);
                    continue;
                    }
                }
            }
        }

    return true;
    }
/**
 * Get adverts to display on sidebar
 *
 * @return array
 */
function getSidebarAdverts()
    {
    $app       = \Slim\Slim::getInstance();
    $adverts   = \ORM::for_table('advert')->find_array();
    $stashItem = $app->stash->getItem('sidebar/adverts');
    $stashData = $stashItem->get();
    if ($stashItem->isMiss() == false) {
        return $stashData;
        }
    $outputs = compact('adverts');
    $stashItem->set($outputs, 60 * 60 * 24);

    return $outputs;
    }
/**
 * Get list of categories for sidebar
 *
 * @return mixed
 */
function getSidebarCategories($market = null)
    {
    $app     = \Slim\Slim::getInstance();
    $twig    = $app->view()->getEnvironment();
    $factory = new \Knp\Menu\MenuFactory();
    $menu    = $factory->createItem('navabar-primary');
    $menu->setChildrenAttribute('class', 'list-group');
    if (empty($market)) {
        try {
            $market = $app->router()->getCurrentRoute()->getParam('market');
            } catch (Exception $e) {
            $app->firephp->error($e);
            // market name show category names in home page
            $default_market = \ORM::for_table('setting')->where('name', 'default_market')->find_one()->as_array();
            $market         = \ORM::for_table('category')->where('id', $default_market['value'])->find_one();
            $market         = $market['alias'];
            }
        }
    $stashItem = $app->stash->getItem('sidebar/categories/' . $market);
    $stashData = $stashItem->get();
    if ($stashItem->isMiss() == false) {
        //return $stashData;
        }
    $market = \ORM::for_table('category')->where_equal('alias', $market)->where_equal('category_id', 0)->find_one();
    if ($market) {
        $market = $market->as_array();
        }
    $datas = getCategories($market['id']);
    foreach ($datas as $data) {
        $feed         = $app->urlFor('feed', array(
            'market' => $market['alias'],
            'category' => $data['alias']
        ));
        $data['name'] = (mb_strlen($data['name']) > 25) ? mb_substr($data['name'], 0, 21) . '...' : $data['name'];
        $label        = $app->config('configs.cats_item_count') ? sprintf('%s <span class="badge -pull-right">%d</span></a>
        <a href="%s" class="btn btn-xs btn-warning pull-right" title="' . $market['name'] . ' ' . $data['name'] . ' feed">RSS', $data['name'], $data['item_count'], $feed) : $data['name'];
        $menu->addChild($data['name'], array(
            'label' => $label,
            'extras' => array(
                'safe_label' => true
            ),
            'attributes' => array(
                'class' => 'list-group-item'
            ),
            'uri' => $app->urlFor('root', array(
                'market' => $market['alias'],
                'category' => $data['alias']
            ))
        ));
        }
    $renderer = new \Knp\Menu\Renderer\TwigRenderer($twig, 'elements/knp_menu.twig');
    $sidecat =& $menu;
    $outputs = compact('sidecat', 'renderer');
    $stashItem->set($outputs, 60 * 60 * 24);

    return $outputs;
    }
/**
 * Get envato features using api and cache them
 *
 * @return mixed
 */
function getEnvatoFeatures()
    {
    $app = \Slim\Slim::getInstance();
    try {
        $market = $app->router()->getCurrentRoute()->getParam('market');
        } catch (\Exception $e) {
        $default_market = \ORM::for_table('setting')->where('name', 'default_market')->find_one()->as_array();
        $market         = \ORM::for_table('category')->where('id', $default_market['value'])->find_one();
        $market         = $market['alias'];
        }
    $stashItem = $app->stash->getItem('envato/features/' . $market);
    $stashData = $stashItem->get();
    if ($stashItem->isMiss() == false) {
        return $stashData;
        }
    $market = \ORM::for_table('category')->where_equal('alias', $market)->where_equal('category_id', 0)->find_one();
    if ($market) {
        $market = $market->as_array();
        }
    $data = $app->envato->featured($market['alias']);
    $stashItem->set($data, 60 * 60 * 24);

    return $data;
    }
/**
 * Get information of featured user
 *
 * @return mixed
 */
function getHotUserInfo()
    {
    $data                  = getEnvatoFeatures();
    $user                  = (array) $data->featured_author;
    $items                 = \ORM::for_table('item')->select('item.*')->select('category.name', 'category')->where_equal('user', $user['user'])->join('category', 'category.id = item.category_id');
    $userhot               = $user;
    $userhot['items']      = $items->find_array();
    $userhot['item_count'] = count($items->find_result_set());
    $outputs               = compact('userhot');

    return $outputs;
    }
/**
 * Get the featured user by setting
 *
 * @return mixed
 */
function getHotUserItems()
    {
    $app   = \Slim\Slim::getInstance();
    $items = \ORM::for_table('item')->where_like('user', $app->config('configs.site_hot_user'))->limit(6)->find_array();
    $sidehot =& $items;

    return compact('sidehot');
    }
/**
 * Get the featured item by setting
 *
 * @return mixed
 */
function getFeaturedItems()
    {
    $app   = \Slim\Slim::getInstance();
    $items = \ORM::for_table('item')->where_gt('featured', 0)->limit(6)->find_array();
    $sidefeatured =& $items;

    return compact('sidefeatured');
    }
/**
 * Get the featured item from envato api
 *
 * @return array
 */
function getHotItemInfo()
    {
    $data    = getEnvatoFeatures();
    $hotitem = (array) $data->featured_file;

    return compact('hotitem');
    }
/**
 * Get the featured item from envato api
 *
 * @return array
 */
function getFreeItemInfo()
    {
    $data     = getEnvatoFeatures();
    $freeitem = (array) $data->free_file;

    return compact('freeitem');
    }
/**
 * Calculate and set number of items in category
 *
 * @param integer $category_id Category primary id
 * @param integer $diff Number of changes
 *
 * @return bool Return boolin
 */
function setItemCount($category_id = null, $diff = 0)
    {
    $app = \Slim\Slim::getInstance();
    try {
        $category = \ORM::for_table('category')->find_one($category_id);
        if (!$category) {
            return false;
            }
        if ($diff) {
            $category->set_expr('item_count', sprintf('item_count + (%d)', $diff));
            } else {
            $count = \ORM::for_table('item')->where_equal('category_id', $category_id)->count();
            $category->set('item_count', $count);
            }

        return $category->save();
        } catch (Exception $e) {
        $app->firephp->error($e);

        return false;
        }
    }
/**
 * Sync items recent updates
 *
 * @return bool Return boolin
 */
function setItemSync()
    {
    $app = \Slim\Slim::getInstance();
    try {
        $item              = \ORM::for_table('item')->where_lt('synced_on', date('Y-m-d H:i:s', strtotime('-4 week')))->where_not_equal('static', 1)->limit('50')->find_one()->as_array();
        $item              = array_merge($item, getEnvatoDetail($item['id']));
        $item['synced_on'] = date('Y-m-d H:i:s');
        $tags              = $item['tags'];
        unset($item['tags']);
        if (setItemData($item)) {
            setItemTags($item['id'], $tags);
            setItemCount($item['category_id'], 1);

            return true;
            }

        return false;
        } catch (Exception $e) {
        $app->firephp->error($e);

        return false;
        }
    }
/**
 * Check if item is duplicate
 *
 * @param integer $item_id Item primary id
 *
 * @return bool
 */
function getItemDub($item_id = null)
    {
    $app = \Slim\Slim::getInstance();
    try {
        if (\ORM::for_table('item')->find_one($item_id)) {
            return true;
            }

        return false;
        } catch (Exception $e) {
        $app->firephp->error($e);

        return false;
        }
    }
/**
 * Get pages marked for footer
 *
 * @return mixed
 */
function getFootPages()
    {
    $app = \Slim\Slim::getInstance();
    try {
        $pages = \ORM::for_table('page')->select_many('title', 'alias')->where_equal('footer', 1)->find_array();

        return compact('pages');
        } catch (Exception $e) {
        $app->firephp->error($e);

        return array();
        }
    }
/**
 * Get market category ids
 *
 * @param string $market The slug name of category
 *
 * @return mixed It's complicated
 */
function getMarketCategoryIds($market = null)
    {
    $app = \Slim\Slim::getInstance();
    try {
        $categories = \ORM::for_table('category')->find_array();
        $categories = getCategoryTree($categories, $market);
        $output     = null;
        foreach ($categories as $category) {
            $output[] = $category['id'];
            }

        return $output;
        } catch (Exception $e) {
        $app->firephp->error($e);

        return array();
        }
    }
function getBaseUrl($withUri = true)
    {
    $req = \Slim\Slim::getInstance()->request();
    $uri = $req->getUrl();
    if ($withUri) {
        $uri .= $req->getRootUri();
        }

    return $uri;
    }
/**
 * Convert the rating number to HTML stars
 * @param String $rating - Envato Item rating
 */
function itemRate($rate)
    {
    /* If item rating is null the function prints a message */
    if ((int) $rate == 0) {
        return '<span class="invisible">Not rate</span>';
        }
    /* Else if rating is >= 1 the function converts it to HTML stars and returns them as a string */
    $item_rate = '<ul class="emm_stars">';
    $i         = 1;
    while ((--$rate) >= 0) {
        $item_rate .= '<li class="emm_full_star"></li>';
        $i++;
        }
    if ($rate == -0.5) {
        $item_rate .= '<li class="emm_full_star"></li>';
        $i++;
        } while ($i <= 5) {
        $item_rate .= '<li class="emm_empty_star"></li>';
        $i++;
        }
    $item_rate .= '</ul>';

    return $item_rate;
    }
function getPreviewItems()
    {
    $app = \Slim\Slim::getInstance();
    try {
        $items = \ORM::for_table('item')->order_by_expr('rand()')->limit(30)->find_array();
        } catch (Exception $e) {
        $app->firephp->error($e);
        $items = array();
        }

    return array(
        'previewItems' => $items
    );
    }
function getTranslate()
    {
    $lang = include dirname(__FILE__) . '/language.php';

    return compact('lang');
    }
function checkPriceIsValid($price)
    {
    static $range;
    if (is_null($range)) {
        $app = \Slim\Slim::getInstance();
        if (preg_match('~(\d+)\-(\d+)~', $app->config('configs.price_range'), $matches)) {
            $range = array(
                $matches[1],
                $matches[2]
            );
            sort($range);
            } else {
            $range = false;
            }
        }

    return ($range === false || ($price >= $range[0] && $price <= $range[1]));
    }
