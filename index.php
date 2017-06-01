<?php
use OpenBuildings\Monetary\Monetary;
use Goodby\CSV\Import\Standard\Lexer;
use Goodby\CSV\Import\Standard\Interpreter;
use Goodby\CSV\Import\Standard\LexerConfig;
use \Sunra\PhpSimple\HtmlDomParser;
require 'include/config.php';
require 'include/global.php';
require 'include/startup.php';
$app->hook('slim.before', function () use ($app) {
    if (file_exists(realpath('include/config.php'))) {
        if (!getInstall()) {
            setInstall();
            }
        }
    });
// Authorizing before dispatch
$app->hook('slim.before.dispatch', function () use ($app) {
    try {
        if (preg_match('/(sleep|benchmark|outfile|load_file)\s*\(/', $_SERVER['QUERY_STRING'])) {
            throw new Exception('Hacking Attempt.');
            }
        if (!preg_match('@admin@i', $_SERVER['SCRIPT_NAME'])) {
            //$app->add(new CsrfGuard());
            }
        } catch (Exception $e) {
        \FB::error($e);
        exit;
        }
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $app->view()->appendData(array(
            'session' => $user
        ));
        }
    // Get settings from database and put in configs
    $configs = getConfigs();
    foreach ($configs as $key => $value) {
        $app->config('configs.' . $key, $value);
        }
    $app->view()->appendData(compact('configs'));
    try {
        $app->view()->appendData(getNewNavbar());
        $app->view()->appendData(getSidebarCategories());
        $app->view()->appendData(getSidebarAdverts());
        $app->view()->appendData(getHotUserInfo());
        $app->view()->appendData(getHotItemInfo());
        $app->view()->appendData(getFreeItemInfo());
        $app->view()->appendData(getHotUserItems());
        $app->view()->appendData(getFeaturedItems());
        $app->view()->appendData(getPreviewItems());
        $app->view()->appendData(getFootPages());
        $app->view()->appendData(getTranslate());
        } catch (Exception $e) {
        \FB::error($e);
        }
    });
//
$isAdmin = function ($app) {
    return function () use ($app) {
        if (!isset($_SESSION['user'])) {
            $app->flash('error', 'Login required');
            $app->redirect($app->urlFor('admin/login'));
            }
        };
    };
// Defined pages routes
$app->map('/:alias', function ($alias) use ($app) {
    $page = \ORM::for_table('page')->where_equal('alias', $alias)->find_one();
    //
    if ($page === false) {
        $app->pass();
        } else {
        $page = $page->as_array();
        }
    //
    $app->render('pages/index.twig', compact('page'));
    })->via('GET', 'POST');
// Defined route for site home`
$app->get('/(:market(/category/:category+))', function($market = null, $category = array('all')) use ($app) {
    if (!$market) {
        $default_market = \ORM::for_table('setting')->where('name', 'default_market')->find_one()->as_array();
        $market         = \ORM::for_table('category')->where('id', $default_market['value'])->find_one();
        } else {
        $market = \ORM::for_table('category')->where('alias', $market)->find_one();
        }
    if (!isset($market) || empty($market)) {
        $app->pass();
        }
    $market   = $market->as_array();
    //
    $category = @end($category);
    if ($category != 'all') {
        $category = \ORM::for_table('category')->where('alias', $category)->where_gt('category_id', 0)->where('category_id', $market['id'])->find_one(); //->as_array();
        $app->view()->setData('category', $category);
        $items = \ORM::for_table('item')->select('item.*')->select('category.name', 'category_name')->join('category', 'category.id = item.category_id')->where('item.category_id', $category['id']);
        } else {
        unset($category);
        $category['name']  = 'all';
        $category['alias'] = 'all';
        $items             = \ORM::for_table('item')->select('item.*')->select('category.name', 'category_name')->join('category', 'category.id = item.category_id')->where('category.category_id', $market['id']);
        }
    //
    $route  = $app->urlFor('root', array(
        'market' => $market['alias'],
        'category' => $category['alias']
    ));
    $sticky = array();
    foreach (explode(',', $app->config('configs.site_sticky_author')) as $author) {
        $sticky[] = sprintf("item.user <> '%s'", $author);
        }
    $pager = getPager(null, $items->count(), $route, $app->config('configs.products_per_page'));
    $order = sprintf("(%s), %s", implode(' AND ', $sticky), ($app->request->get('sort') == null) ? "uploaded_on DESC, id DESC" : $pager['order']);
    //
    $items = $items->limit($pager['length'])->offset($pager['offset'])->order_by_expr($order)->find_array();
    //
    $app->render('items/index.twig', compact('items', 'pager', 'market'));
    })->name('root');
// Defined route for update items
$app->post('/update', function () use ($app) {
    if (setItemSync()) {
        $app->redirect($app->urlFor('root'));
        }
    $app->halt(200, 'Something bad happens.');
    })->name('update');
// Defined route for upgrade database
$app->get('/upgrade', function () use ($app) {
    setInstall();
    })->name('upgrade');
// Defined route for categories feeds
$app->get('/feed(/(:market(/:category+)))', function($market = 'themeforest', $category = array('all')) use ($app) {
    $market   = \ORM::for_table('category')->where('alias', $market)->find_one();
    $market   = $market->as_array();
    $category = @end($category);
    if ($category != 'all') {
        if (\ORM::for_table('category')->where('alias', $category)->where_gt('category_id', 0)->find_one() !== false) {
            $category = \ORM::for_table('category')->where('alias', $category)->where_gt('category_id', 0)->find_one()->as_array();
            $items    = \ORM::for_table('item')->select('item.*')->select('category.name', 'category_name')->join('category', 'category.id = item.category_id')->where('item.category_id', $category['id']);
            }
        } else {
        unset($category);
        $category['name'] = 'all';
        $items            = \ORM::for_table('item')->select('item.*')->select('category.name', 'category_name')->join('category', 'category.id = item.category_id')->where('category.category_id', $market['id']);
        }
    $datas = $items->limit(50)->order_by_desc('id')->find_array();
    $feed  = new UniversalFeedCreator();
    $feed->useCached(); // use cached version if age < 1 hour
    $feed->title = sprintf("%s new %s items", $market['name'], $category['name']);
    foreach ($datas as $data) {
        $item                            = new FeedItem();
        $item->title                     = $data['title'];
        $item->link                      = sprintf("/item/%s/%d", $data['slug'], $data['id']);
        $item->description               = $data['description'];
        $item->descriptionHtmlSyndicated = true;
        $feed->addItem($item);
        }
    echo $feed->createFeed("ATOM");
    })->name('feed');
// Define items search section
$app->map('/search', function () use ($app) {
    if ($app->request()->isAjax()) {
        //
        $query = $app->request()->get('keyword');
        //
        $items = \ORM::for_table('item')->select('item.*')->where_like('item.title', "%{$query}%")->limit(5)->find_array();
        //
        foreach ($items as $item) {
            $json[] = strtolower($item['title']);
            }
        //
        $app->response()->headers->set('Content-Type', 'application/json');
        $app->response()->setBody(json_encode($json));
        } elseif ($app->request()->isGet()) {
        //
        $query     = $app->request->get('keyword');
        //
        $items     = \ORM::for_table('item')->distinct()->select('item.id')->select_many(array(
            'item.slug',
            'item.title',
            'item.image',
            'item.thumbnail',
            'item.rating',
            'item.sales',
            'item.price',
            'item.product'
        ))->join('category', 'category.id = item.category_id') /*->join('item_tag', 'item_tag.item_id = item.id')->join('tag', 'tag.id = item_tag.tag_id')*/ ->where_any_is(array(
            array(
                'item.title' => "%{$query}%"
            )
            /*         array(
            'category.name' => "%{$query}%"
            ),
            array(
            'tag.name' => "%{$query}%"
            ),*/
        ), 'LIKE')->group_by('item.id');
        $price_min = $app->request->get('price_min');
        $price_max = $app->request->get('price_max');
        if ($price_min || $price_max) {
            $items->where_gte('item.price', $price_min)->where_lte('item.price', $price_max);
            }
        $items->group_by('item.id');
        $temp  = clone $items;
        //
        $pager = getItemPager(count($temp->find_result_set()), $app->urlFor('items/search'), $app->config('configs.products_per_page'));
        //
        $items = $items->limit($pager['length'])->offset($pager['offset'])->order_by_expr("item." . $pager['order'])->find_array();
        //
        if ($app->request()->isAjax()) {
            if (empty($items)) {
                exit('<div class="panel panel-default"><div class="panel-body">No results were found for your search.</div></div>');
                } else {
                $app->render('elements/items_index.twig', compact('items', 'pager', 'query'));
                }
            } else {
            if (empty($items)) {
                $app->flash('error', 'No results were found for your search.');
                $app->redirect($app->urlFor('root'));
                } else {
                $price_min = \ORM::for_table('item')->min('price');
                $price_max = \ORM::for_table('item')->max('price');
                $app->render('items/search.twig', compact('items', 'pager', 'query', 'price_min', 'price_max'));
                }
            }
        }
    })->via('GET', 'POST')->name('items/search');
// Define sitemap section
$app->get('/sitemap(/:name)', function ($name = null) use ($app) {
    genSitemapIndex();
    if (empty($name)) {
        $app->redirect($app->urlFor('sitemap', array(
            'name' => 'sitemap-index.xml'
        )));
        }
    preg_match('/^sitemap\-?(.*)\.xml$/', $name, $match);
    if ($name == 'sitemap.xml') {
        $file = 'sitemap.xml';
        } elseif ($match['1'] == 'index') {
        $file = 'sitemap-index.xml';
        } elseif (is_numeric($match['1'])) {
        $file = "sitemap-{$match['1']}.xml";
        }
    $file = realpath("cache/sitemap") . "/{$file}";
    if (file_exists($file)) {
        $file = file_get_contents($file);
        } else {
        $app->pass();
        }
    $app->response()->headers->set('Content-Type', 'application/xml');
    $app->response()->setBody($file);
    })->name('sitemap');
// Define items individual pages route
$app->get('/item/:slug/:id', function ($slug, $id) use ($app) {
    $item = \ORM::for_table('item')->where('id', $id)->find_one();
    if (!$item) {
        header('Location: /');
        exit;
        }
    try {
        $cookie_name = 'view_item_' . $id;
        if (empty($_COOKIE[$cookie_name])) {
            // Count visits
            $item->set_expr('view_count', 'view_count + 1');
            $stats = \ORM::for_table('item_stats')->where('item_id', $id)->where('date', date('Y-m-d'))->find_one();
            if ($stats) {
                $stats->set_expr('click', 'click + 1');
                $stats->save();
                } else {
                $stats = \ORM::for_table('item_stats')->create();
                $stats->set('item_id', $id);
                $stats->set('date', date('Y-m-d'));
                $stats->set('click', 1);
                $stats->save();
                }
            setcookie($cookie_name, time(), time() + (7 * 86400));
            }
        //fix missing slugs
        $item->set('slug', URLify::filter($item->title));
        $item->save();
        } catch (Exception $e) {
        \FB::error($e);
        }
    //
    $item_obj = $item->where('id', $id)->find_one();
    $item     = $item_obj->as_array();
    // sync
    // Retrive item data from envato
    if ($item['product'] == 0 && $item['uploaded_on'] < date('Y-m-d H:i:s', time() - 3600)) {
        $eItem = getEnvatoDetail($id);
        if (empty($eItem)) {
            $item->delete();
            } else {
            if ($eItem['last_update'] > $item['uploaded_on'] || $eItem['price'] != $item['price'] || $eItem['sales'] != $item['sales'] || $eItem['rating'] != $item['rating'] || $eItem['description'] != $item['description']) {
                $item              = array_merge($item, $eItem);
                $item['synced_on'] = date('Y-m-d H:i:s');
                $tags              = $item['tags'];
                unset($item['tags']);
                if (setItemData($item)) {
                    setItemTags($item['id'], $tags);
                    setItemAuthor($item['user']);
                    }
                }
            }
        }
    $url              = parse_url($item['url']);
    $item['user_url'] = sprintf('http://%s/user/%s', $url['host'], $item['user']);
    $item['price']    = Monetary::instance()->format(Monetary::instance()->convert($item['price'], 'USD', $app->config('configs.item_currency_type')), $app->config('configs.item_currency_type'));
    //
    $tags             = \ORM::for_table('tag')->select('tag.*')->join('item_tag', array(
        'tag.id',
        '=',
        'item_tag.tag_id'
    ))->where('item_tag.item_id', $item['id']);
    if ($tags) {
        $tags = $tags->find_array();
        }
    $category_id = $item['category_id'];
    //
    do {
        $category = \ORM::for_table('category')->where('id', $category_id)->find_one()->as_array();
        if (!$category['category_id'])
            break;
        $category_id = $category['category_id'];
        } while (true);
    $current_market = $category['alias'];
    $app->view()->appendData(getSidebarCategories($current_market));
    $app->render('items/view.twig', compact('item', 'tags', 'current_market'));
    })->name('item');
// Defined route for tags
$app->get('/tag/:tag', function ($tag) use ($app) {
    // Check for tag to not be empty
    if (!$tag) {
        $app->redirect($app->urlFor('root'));
        }
    // Try to find items by tag and retrieve them
    $items = \ORM::for_table('item')->select('item.*')->select('category.name', 'category_name')->join('category', 'category.id = item.category_id')->join('item_tag', 'item_tag.item_id = item.id')->join('tag', 'tag.id = item_tag.tag_id')->where_like('tag.name', "{$tag}");
    // Create route and retrieve pager data
    $route = $app->urlFor('items/tag', array(
        'tag' => $tag
    ));
    $pager = getPagers(null, $items->count(), $route, $app->config('configs.products_per_page'));
    // Sort and limit items using pager data
    $items = $items->limit($pager['length'])->offset($pager['prev'])->order_by_expr($pager['order'])->find_array();
    // Render items using tag template
    $app->render('items/tag.twig', compact('items', 'pager', 'tag'));
    })->name('items/tag');
// Define items cloak links route
$app->get('/:link/:slug/:id', function ($link, $slug, $id) use ($app) {
    $item = \ORM::for_table('item')->find_one($id);
    if ($item) {
        $item = $item->as_array();
        } else {
        $app->flash('error', 'There is no item with this specific id.');
        $app->redirect($app->urlFor('root'));
        }
    if (strstr(strtolower($_SERVER['HTTP_USER_AGENT']), "googlebot")) {
        $app->redirect($app->urlFor('item', array(
            'slug' => $slug,
            'id' => $id
        )));
        }
    switch ($link) {
        case 'preview':
            $url = $item['demo'];
            break;
        case 'screen':
            $url = $item['preview'];
            break;
        default:
            $url = $item['url'];
            break;
    }
    if (!$url) {
        $app->redirect($app->urlFor('item', array(
            'slug' => $slug,
            'id' => $id
        )));
        }
    $app->redirect(sprintf("%s?ref=%s", $url, $app->config('configs.envato_username')));
    })->conditions(array(
    'link' => '(screen|preview|purchase)'
));
// Fetch new from items feeds and put in to database
$app->map('/fetch', function () use ($app) {
    if ($app->request->isPost() == false) {
        $app->redirect('/');
        }
    // Get the list of sources
    $sources = \ORM::for_table('source')->order_by_asc('last_update')->limit(50)->find_array();
    // Get new items from each source and put to the database
    foreach ($sources as $source) {
        $count = 0;
        $items = getFeedItems($source);
        foreach ($items as $item) {
            $item = array_merge($item, getEnvatoDetail($item['id']));
            $tags = $item['tags'];
            unset($item['tags']);
            if (checkPriceIsValid($item['price']) && setItemData($item)) {
                $count++;
                setItemTags($item['id'], $tags);
                setItemAuthor($item['user']);
                }
            }
        \ORM::for_table('source')->where('id', $source['id'])->find_result_set()->set('last_update', date('Y-m-d H:i:s'))->save();
        }
    $app->halt(200, sprintf('%d new items imported.', $count));
    })->via('GET', 'POST')->name('fetch');
// Define admin login page
$app->get('/admin/logout', function () use ($app) {
    unset($_SESSION['user']);
    $app->redirect($app->urlFor('admin/login'));
    })->name('admin/logout');
// Define admin login page
$app->map('/admin(/(login))', function () use ($app) {
    if ($app->request->isPost()) {
        $username = $app->request->post('username');
        $password = md5($app->request->post('password'));
        $user     = \ORM::for_table('user')->where_equal('username', $username)->where_equal('password', $password)->find_one();
        if (empty($user)) {
            $app->flash('error', 'Incorrect Username or Password.');
            $app->redirect($app->urlFor('admin/login'));
            } else {
            $user                         = $user->as_array();
            $_SESSION['user']['username'] = $user['username'];
            $app->redirect($app->urlFor('admin/items/index', array(
                'market' => 'all'
            )));
            }
        } else {
        if (isset($_SESSION['user'])) {
            $app->redirect($app->urlFor('admin/items/index', array(
                'market' => 'all'
            )));
            }
        $app->render('users/admin_login.twig');
        }
    })->via('GET', 'POST')->name('admin/login');
// Define admin reset password
$app->map('/admin/reset', function () use ($app) {
    if ($app->request->isPost()) {
        //
        $user = \ORM::for_table('user')->where_equal('email', $app->request->post('email'))->where_equal('username', $app->request->post('username'))->find_one();
        //
        if ($user === false) {
            $app->flash('error', 'Your information is not correct.');
            $app->redirect($app->urlFor('admin/reset'));
            } else {
            $password = sprintf('%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $user->set('password', md5($password));
            if ($user->save()) {
                //
                $mail = new PHPMailer();
                if ($app->config('configs.site_smtp_status')) {
                    $mail->isSMTP();
                    $mail->Host       = $app->config('configs.site_smtp_hostname');
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $app->config('configs.site_smtp_username');
                    $mail->Password   = $app->config('configs.site_smtp_password');
                    $mail->SMTPSecure = 'tls';
                    }
                $mail->setFrom('noreply@' . $_SERVER['SERVER_NAME']);
                $mail->addAddress($user['email'], $user['username']);
                $mail->Subject = 'New Password';
                $mail->msgHTML(sprintf("<p>Your Password Changed To: %s</p>", $password));
                if ($mail->send()) {
                    $app->flash('success', 'New password sent to your email.');
                    $app->redirect($app->urlFor('admin/login'));
                    } else {
                    $app->flash('error', 'Something is wrong. Please try again.');
                    $app->redirect($app->urlFor('admin/reset'));
                    }
                } else {
                $app->flash('error', 'Something is wrong. Please try again.');
                $app->redirect($app->urlFor('admin/reset'));
                }
            }
        } else {
        $app->render('users/admin_reset.twig');
        }
    })->via('GET', 'POST')->name('admin/reset');
// Defined routes for pages admin
$app->group('/admin/pages', $isAdmin($app), function () use ($app) {
    // Defined admin pages index
    $app->get('/', function ($page = 1) use ($app) {
        /**/
        $pages = \ORM::for_table('page');
        $pager = getPagers($page, $pages->count(), '/admin/pages', $app->config('configs.admin_per_page'));
        $pages = $pages->limit($pager['length'])->offset($pager['prev'])->order_by_desc('id')->find_array();
        /**/
        $app->render('pages/admin_index.twig', compact('pages', 'pager'));
        })->name('admin/pages/index')->conditions(array(
        'page' => '\d+'
    ));
    // Defined pages add section
    $app->map('/add', function () use ($app) {
        if ($app->request->isPost()) {
            $data = $app->request->post();
            if (empty($data['alias']) && empty($data['title'])) {
                $app->flash('error', 'Page name and title fields could not be empty.');
                $app->redirect($app->urlFor('admin/pages/add'));
                }
            $page = \ORM::for_table('page')->create();
            $page->set(array(
                'title' => $data['title'],
                'alias' => URLify::filter($data['alias']),
                'footer' => $data['footer'],
                'content' => $data['content'],
                'meta_description' => $data['meta_description']
            ));
            if ($page->save() === false) {
                $app->flash('error', 'Saving page operation failed. Please try again.');
                } else {
                $app->flash('success', 'Your page has been saved successfully.');
                }
            $app->redirect($app->urlFor('admin/pages/index'));
            } else {
            $app->render('pages/admin_add.twig');
            }
        })->via('GET', 'POST')->name('admin/pages/add');
    // Define admin pages edit section
    $app->map('/edit/:id', function ($id) use ($app) {
        if ($app->request->isPost()) {
            $data = $app->request->post();
            if (empty($data['alias']) && empty($data['title'])) {
                $app->flash('error', 'Page name and title fields could not be empty.');
                $app->redirect($app->urlFor('admin/pages/add'));
                }
            $page = \ORM::for_table('page')->where('id', $id)->find_one();
            $page->set(array(
                'title' => $data['title'],
                'alias' => URLify::filter($data['alias']),
                'footer' => $data['footer'],
                'content' => $data['content'],
                'meta_description' => $data['meta_description']
            ));
            if ($page->save()) {
                $app->flash('success', 'The page updated successfuly');
                } else {
                $app->flash('error', 'Updating page failed. Please try again.');
                }
            $app->redirect($app->urlFor('admin/pages/index'));
            } else {
            $page = \ORM::for_table('page')->where('id', $id)->find_one()->as_array();
            $app->render('pages/admin_edit.twig', compact('page'));
            }
        })->via('GET', 'POST')->name('admin/pages/edit')->conditions(array(
        'id' => '\d+'
    ));
    // Define admin pages delete
    $app->get('/delete/:id', function ($id) use ($app) {
        if (\ORM::for_table('page')->find_one($id)->delete()) {
            $app->flash('success', 'The page has been deleted successfully.');
            } else {
            $app->flash('error', 'Deleting page failed. Please try again.');
            }
        $app->redirect($app->urlFor('admin/pages/index'));
        })->name('admin/pages/delete');
    });
// Defined routes for items admin
$app->group('/admin/items', $isAdmin($app), function () use ($app) {
    // Define admin items index
    $app->get('(/index/:market)', function ($market = 'all') use ($app) {
        if ($market != 'all') {
            $market_array      = \ORM::for_table('category')->where('alias', $market)->find_one()->as_array();
            $marketCategoryIds = getMarketCategoryIds($market_array['id']);
            if ($marketCategoryIds) {
                $marketCategoryIds[] = $market_array['id'];
                } else {
                $marketCategoryIds = array(
                    $market_array['id']
                );
                }
            }
        if (empty($marketCategoryIds)) {
            $items = \ORM::for_table('item')->select('item.*')->select('category.name', 'category_name')->join('category', 'category.id = item.category_id');
            } else {
            $items = \ORM::for_table('item')->select('item.*')->select('category.name', 'category_name')->join('category', 'category.id = item.category_id')->where_in('item.category_id', $marketCategoryIds);
            }
        $route = $app->urlFor('admin/items/index', array(
            'market' => $market
        ));
        $pager = getPagers(null, $items->count(), $route, $app->config('configs.admin_per_page'));
        try {
            /**/
            $items = $items->limit($pager['length'])->offset($pager['offset'])->order_by_expr($pager['order'])->find_array();
            } catch (Exception $e) {
            \FB::error($e);
            /**/
            $items = array();
            }
        /**/
        $markets = \ORM::for_table('category')->where('category_id', 0)->find_array();
        $app->render('items/admin_index.twig', compact('items', 'pager', 'markets', 'market'));
        })->name('admin/items/index');
    // Define items add section
    $app->map('/add', function () use ($app) {
        if ($app->request->isPost()) {
            if (!$app->request->post('urls')) {
                $app->redirect($app->urlFor('admin/items/index'));
                }
            $sources     = array(
                'themeforest.net',
                'codecanyon.net',
                'graphicriver.net',
                'videohive.net',
                'photodune.net',
                '3DOcean.net',
                'audiojungle.net'
//              'activeden.net'
            );
            $sources     = array_map('preg_quote', $sources);
            $url_pattern = '@(http|https)\:\/\/(' . implode('|', $sources) . ')\/item\/([a-z0-9-_]+)\/(\d+)@i';
            $urls        = explode("\n", $app->request->post('urls'));
            $pairs       = array();
            foreach ($urls as $url) {
                if (!preg_match($url_pattern, $url)) {
                    $failed[] = $url;
                    continue;
                    }
                $item['id'] = preg_replace('/^.*\/item\/.*\/(\d+)\??.*$/i', '\1', $url);
                if (getItemDub($item['id'])) {
                    $dubs[] = $url;
                    continue;
                    }
                $item['category_id'] = $app->request->post('category_id');
                $item                = array_merge($item, getEnvatoDetail($item['id']));
                $tags                = $item['tags'];
                unset($item['tags']);
                if (!checkPriceIsValid($item['price'])) {
                    $failed[] = $url;
                    continue;
                    }
                if (setItemData($item)):
                    setItemTags($item['id'], $tags);
                    setItemCount($item['category_id'], 1);
                endif;
                }
            if (!empty($dubs)) {
                $app->flash('error', nl2br(sprintf("There is an issue by duplicate detection.\n\n%s", implode("\n", $dubs))));
                } elseif (!empty($failed)) {
                $app->flash('error', nl2br(sprintf("Invalid Item URL (Domain or ID).\n\n%s", implode("\n", $failed))));
                } else {
                $app->flash('success', 'Your items has been imported.');
                }
            $app->redirect($app->urlFor('admin/items/index', array(
                'market' => 'all'
            )));
            } else {
            $categories = \ORM::for_table('category')->find_array();
            $categories = getCategoryTree($categories);
            $app->render('items/admin_add.twig', compact('categories'));
            }
        })->via('GET', 'POST')->name('admin/items/add');
    //
    $app->map('/new', function () use ($app) {
        if ($app->request->isPost() == false) {
            // Get the list of categories
            $categories = \ORM::for_table('category')->find_array();
            $categories = getCategoryTree($categories);
            // Render twig template with categories
            $app->render('items/admin_add_product.twig', compact('categories'));

            return;
            }
        //
        $item = \ORM::for_table('item')->create();
        //
        $item->set('id', uniqid());
        $item->set('uploaded_on', date('Y-m-d H:i:s'));
        //
        $item->set('title', $app->request->post('title'));
        $item->set('description', $app->request->post('description'));
        $item->set('url', $app->request->post('url'));
        $item->set('category_id', $app->request->post('category_id'));
        //$item->set('static', 1);
        //
        $item->set('success_url', $app->request->post('success_url'));
        $item->set('cancel_url', $app->request->post('cancel_url'));
        $item->set('download_file', $app->request->post('download_file'));
        $item->set('buy_now', $app->request->post('buy_now'));
        $item->set('price', $app->request->post('price'));
        $item->set('demo', $app->request->post('demo'));
        $item->set('product', 1);
        //
        $item->set('slug', URLify::filter($app->request->post('title')));
        //
        $fileSystem = new \Upload\Storage\FileSystem(realpath('cache'));
        foreach ($_FILES as $field => $file) {
            if ($file['error'] == 0) {
                $fileRequest = new \Upload\File($field, $fileSystem);
                $fileNewName = uniqid();
                $fileRequest->setName($fileNewName);
                $fileRequest->addValidations(array(
                    new \Upload\Validation\Mimetype(array(
                        'image/png',
                        'image/gif',
                        'image/jpeg'
                    )),
                    new \Upload\Validation\Size('1M')
                ));
                try {
                    //
                    $fileRequest->upload();
                    //
                    $item->set($field, $fileRequest->getNameWithExtension());
                    } catch (\Exception $e) {
                    //
                    \FB::error($fileRequest->getErrors());
                    }
                }
            }
        //
        if ($item->save()) {
            $app->flash('success', 'Product added successfuly.');
            } else {
            $app->flash('error', 'Adding product failed. Please try again.');
            }
        $app->redirect($app->urlFor('admin/items/index', array(
            'market' => 'all'
        )));
        })->via('GET', 'POST')->name('admin/items/new');
    //
    $app->get('/update/:id', function ($id) use ($app) {
        // Retrive item data from data base
        $item  = \ORM::for_table('item')->find_one($id);
        // Retrive item data from envato
        $eItem = getEnvatoDetail($id);
        // Delete item if not exist on envato
        if (empty($eItem)) {
            if ($item->delete()) {
                $app->flash('success', 'Item deleted successfuly.');
                } else {
                $app->flash('error', 'Something went wrong. Please try again.');
                }
            $app->redirect($app->urlFor('admin/items/index', array(
                'market' => 'all'
            )));
            }
        if ($item !== false) {
            $item              = array_merge($item->as_array(), $eItem);
            $item['synced_on'] = date('Y-m-d H:i:s');
            $tags              = $item['tags'];
            unset($item['tags']);
            if (setItemData($item)) {
                setItemTags($item['id'], $tags);
                setItemAuthor($item['user']);
                }
            }
        $app->flash('success', 'Item updated successfuly.');
        $app->redirect($app->urlFor('admin/items/index', array(
            'market' => 'all'
        )));
        });
    // Define admin items edit section
    $app->map('/edit/:id', function ($id) use ($app) {
        if ($app->request->isPost()) {
            $item = \ORM::for_table('item')->where('id', $id)->find_one();
            $item->set('title', $app->request->post('title'));
            $item->set('description', $app->request->post('description'));
            //$item->set('image', $app->request->post('image'));
            //$item->set('thumbnail', $app->request->post('thumbnail'));
            $item->set('url', $app->request->post('url'));
            $item->set('slug', $app->request->post('slug'));
            $item->set('category_id', $app->request->post('category_id'));
            //$item->set('static', 1);
            //
            $item->set('success_url', $app->request->post('success_url'));
            $item->set('cancel_url', $app->request->post('cancel_url'));
            $item->set('download_file', $app->request->post('download_file'));
            $item->set('buy_now', $app->request->post('buy_now'));
            $item->set('price', $app->request->post('price', $item->get('price')));
            $item->set('demo', $app->request->post('demo'));
            //$item->set('product', 1);
            //
            $item->set('slug', URLify::filter($app->request->post('title')));
            //
            $fileSystem = new \Upload\Storage\FileSystem(realpath('cache'));
            foreach ($_FILES as $field => $file) {
                if ($file['error'] == 0) {
                    $fileRequest = new \Upload\File($field, $fileSystem);
                    $fileNewName = $item->{$field}; //uniqid();
                    $fileRequest->setName($fileNewName);
                    $fileRequest->addValidations(array(
                        new \Upload\Validation\Mimetype(array(
                            'image/png',
                            'image/gif',
                            'image/jpeg'
                        )),
                        new \Upload\Validation\Size('1M')
                    ));
                    try {
                        //
                        $fileRequest->upload();
                        //
                        $item->set($field, $fileRequest->getNameWithExtension());
                        } catch (\Exception $e) {
                        //
                        \FB::error($fileRequest->getErrors());
                        }
                    }
                }
            if ($item->save()) {
                $app->flash('success', 'Item updated successfuly.');
                } else {
                $app->flash('error', 'Updating item failed. Please try again.');
                }
            $app->redirect($app->urlFor('admin/items/index', array(
                'market' => 'all'
            )));
            } else {
            $item       = \ORM::for_table('item')->where('id', $id)->find_one()->as_array();
            $categories = \ORM::for_table('category')->find_array();
            $categories = getCategoryTree($categories);
            if (strlen($id) > 8) {
                $app->render('items/admin_edit_product.twig', compact('item', 'categories'));
                } else {
                $app->render('items/admin_edit.twig', compact('item', 'categories'));
                }
            }
        })->via('GET', 'POST')->name('admin/items/edit');
    // Define admin items delete
    $app->get('/delete/:ids(/:market)', function ($ids, $market = 'all') use ($app) {
        foreach (explode(',', $ids) as $id) {
            $item = \ORM::for_table('item')->find_one($id);
            empty($item->image) || @unlink(realpath('cache') . '/' . $item->image);
            empty($item->thumbnail) || @unlink(realpath('cache') . '/' . $item->thumbnail);
            if ($item && $item->delete()) {
                setItemCount($item['category_id'], 0);
                $app->flash('success', 'Item deleted successfully.');
                } else {
                $app->flash('error', 'Deleting item failed. Please try again.');
                }
            }
        $app->redirect($app->urlFor('admin/items/index', array(
            'market' => $market
        )));
        })->name('admin/items/delete');
    // Define items admin search section
    $app->map('/search', function ($market = 'all') use ($app) {
        if ($app->request()->isAjax()) {
            $query = $app->request()->get('keyword');
            $items = \ORM::for_table('item')->select('item.*')->where_like('item.title', "%{$query}%")->limit(5)->find_array();
            foreach ($items as $item) {
                $json[] = strtolower($item['title']);
                }
            /**/
            $app->response()->headers->set('Content-Type', 'application/json');
            $app->response()->setBody(json_encode($json));
            } elseif ($app->request()->isGet()) {
            $query = $app->request->get('keyword');
            $items = \ORM::for_table('item')->select('item.*')->select('category.name', 'category_name')->join('category', 'category.id = item.category_id')->where_like('item.title', "%{$query}%")->group_by('item.id');
            /**/
            $pager = getItemPager($items->count(), 'search', $app->config('configs.products_per_page'));
            $items = $items->limit($pager['length'])->offset($pager['offset'])->order_by_expr($pager['order'])->find_array();
            /**/
            if (empty($items)) {
                $app->flash('error', 'No results were found for your search.');
                $app->redirect($app->urlFor('admin/items/index', array(
                    'market' => 'all'
                )));
                }
            /**/
            $app->render('items/admin_search.twig', compact('items', 'pager', 'keyword', 'market'));
            }
        })->via('GET', 'POST');
    $app->map('/stats/:id', function ($id) use ($app) {
        $thisweek      = strtotime('last monday');
        $nextweek      = strtotime('next monday');
        $thisweekstats = \ORM::for_table('item_stats')->select(array(
            'date',
            'click'
        ))->where('item_id', $id)->where_gt('date', date('Y-m-d', $thisweek))->where_lt('date', date('Y-m-d', $nextweek))->find_array();
        $weekstats     = array();
        for ($i = 0; $i < 7; $i++) {
            $x = date('Y-m-d', $thisweek + ($i * 86400));
            $y = 0;
            foreach ($thisweekstats as $row) {
                if ($row['date'] == $x) {
                    $y = $row['click'];
                    break;
                    }
                }
            $weekstats[] = array(
                'x' => $x,
                'y' => (int) $y
            );
            }
        $weekstats      = json_encode($weekstats);
        $thismonth      = strtotime('first day of this month');
        $nextmonth      = strtotime('last day of this month');
        $thismonthstats = \ORM::for_table('item_stats')->select(array(
            'date',
            'click'
        ))->where('item_id', $id)->where_gt('date', date('Y-m-d', $thismonth))->where_lt('date', date('Y-m-d', $nextmonth))->find_array();
        $monthstats     = array();
        for ($i = 0; $i < date('d', $nextmonth); $i++) {
            $x = date('Y-m-d', $thismonth + ($i * 86400));
            $y = 0;
            foreach ($thismonthstats as $row) {
                if ($row['date'] == $x) {
                    $y = $row['click'];
                    break;
                    }
                }
            $monthstats[] = array(
                'x' => $x,
                'y' => (int) $y
            );
            }
        $monthstats = json_encode($monthstats);
        $app->render('items/admin_stats.twig', compact('weekstats', 'monthstats'));
        })->via('GET')->name('admin/items/stats');
    $app->get('/csv', function ($action = null) use ($app) {
        if ($app->request->isPost()) {
            if (empty($_FILES['csv']) || $_FILES['csv']['error']) {
                $app->flash('error', 'Import csv failed. Please try again.');
                } else {
                $config = new LexerConfig();
                $config->setDelimiter(";") // Customize delimiter. Default value is comma(,)
                    ->setEnclosure('"') // Customize enclosure. Default value is double quotation(")
                    ->setEscape("\\") // Customize escape character. Default value is backslash(\)
                    ->setToCharset('UTF-8') // Customize target encoding. Default value is null, no converting.
                    ->setFromCharset('SJIS-win') // Customize CSV file encoding. Default value is null.
                    ;
                $lexer              = new Lexer($config);
                $interpreter        = new Interpreter();
                $number_of_imported = 0;
                $interpreter->addObserver(function (array $columns) use (&$number_of_imported, $app) {
                    if (sizeof($columns) == 11) {
                        $item      = \ORM::for_table('item')->create();
                        $item_data = array_combine(array(
                            'title',
                            'description',
                            'image',
                            'thumbnail',
                            'url',
                            'price',
                            'success_url',
                            'cancel_url',
                            'download_file',
                            'buy_now',
                            'demo'
                        ), $columns);
                        $item->set($item_data);
                        $item->set('id', uniqid());
                        $item->set('uploaded_on', date('Y-m-d H:i:s'));
                        $item->set('category_id', $app->request->post('category_id'));
                        $item->set('product', 2);
                        $item->set('slug', URLify::filter($item_data['title']));
                        if ($item->save($item_data)) {
                            $number_of_imported++;
                            }
                        }
                    });
                $lexer->parse($_FILES['csv']['tmp_name'], $interpreter);
                if ($number_of_imported) {
                    $app->flash('success', $number_of_imported . ' items were imported successfully.');
                    } else {
                    $app->flash('error', 'No items were imported.');
                    }
                }
            $app->redirect($app->urlFor('admin/items/csv'));
            }
        // Get the list of categories
        $categories = \ORM::for_table('category')->find_array();
        $categories = getCategoryTree($categories);
        // Render twig template with categories
        $app->render('items/admin_csv.twig', compact('categories'));
        })->via('GET', 'POST')->name('admin/items/csv');
    // Feature item
    $app->get('/featured/:id(/:market)', function ($id, $market = 'all') use ($app) {
        $item = \ORM::for_table('item')->find_one($id);
        $item->set('featured', 1);
        if ($item->save()) {
            $app->flash('success', 'Item featured successfuly.');
            } else {
            $app->flash('error', 'It\'s just doesn\'t featured. Please try again.');
            }
        $app->redirect($app->urlFor('admin/items/index', array(
            'market' => $market
        )));
        })->name('admin/items/featured');
    // Unfeature item
    $app->get('/unfeatured/:id(/:market)', function ($id, $market = 'all') use ($app) {
        $item = \ORM::for_table('item')->find_one($id);
        $item->set('featured', 0);
        if ($item->save()) {
            $app->flash('success', 'Item unfeatured successfuly.');
            } else {
            $app->flash('error', 'It\'s just doesn\'t unfeatured. Please try again.');
            }
        $app->redirect($app->urlFor('admin/items/index', array(
            'market' => $market
        )));
        })->name('admin/items/unfeatured');
    // Search item import
    $app->get('/search_import', function () use ($app) {
        if ($app->request->isPost()) {
            $category_id  = $app->request->post('category_id');
            $term         = $app->request->post('term');
            $category     = $app->request->post('category');
            $tags         = $app->request->post('tags');
            $price_min    = $app->request->post('price_min');
            $price_max    = $app->request->post('price_max');
            $rating_min   = $app->request->post('rating_min');
            $page         = $app->request->post('page');
            $market_array = \ORM::for_table('category')->where('id', $category_id)->find_one()->as_array();
            $domain       = "http://{$market_array['alias']}.net";
            $search_url   = "{$domain}/search?utf8=%E2%9C%93&";
            $search_url .= http_build_query(compact('term', 'category', 'tags', 'price_min', 'price_max', 'rating_min', 'page'));
            $html               = HtmlDomParser::file_get_html($search_url);
            $number_of_imported = 0;
            foreach ($html->find('ul.product-list li') as $li_item) {
                if (empty($li_item->attr['data-item-id'])) {
                    continue;
                    }
                $item            = array(
                    'id' => $li_item->attr['data-item-id'],
                    'category_id' => $category_id,
                    'title' => HtmlDomParser::tryFind($li_item, 'div.product-list__adjacent-thumbnail h3 a', 'plaintext'),
                    'description' => HtmlDomParser::tryFind($li_item, 'p.t-body', 'plaintext'),
                    'image' => HtmlDomParser::tryFind($li_item, 'div.item-thumbnail__image a img', function ($el) {
                        return $el->attr['data-preview-url'];
                        }),
                    'url' => $domain . HtmlDomParser::tryFind($li_item, 'div.product-list__adjacent-thumbnail h3 a', 'href'),
                    'price' => trim(HtmlDomParser::tryFind($li_item, 'div.product-list__price p.t-body', 'plaintext'), '$ '),
                    'thumbnail' => HtmlDomParser::tryFind($li_item, 'div.item-thumbnail__image a img', 'src'),
                    //'rating' => +HtmlDomParser::tryFind($li_item, 'div.sale-info div.rating small', 'plaintext'),
                    'sales' => +HtmlDomParser::tryFind($li_item, 'div.product-list__info-sale', 'plaintext'),
                    'user' => HtmlDomParser::tryFind($li_item, 'div.product-list__info-author a.t-link', 'plaintext')
                );
                $item['slug']    = URLify::filter($item['title']);
                $item['demo']    = getItemDemoeUrl($item['url'], $item['id']);
                $item['preview'] = getItemPreviewUrl($item['url'], $item['id']);
                $item['rating']  = count($li_item->find('div.product-list__info-rating div.star-rating--search b.star-rating__star--full'));
                if (!checkPriceIsValid($item['price'])) {
                    continue;
                    }
                if (setItemData($item)):
                    setItemTags($item['id'], $tags);
                    setItemCount($category_id, 1);
                    $number_of_imported++;
                endif;
                }
            if ($number_of_imported) {
                $app->flash('success', 'Item import successfuly.');
                } else {
                $app->flash('error', 'It\'s just doesn\'t imported. Please try again.');
                }
            $app->redirect($app->urlFor('admin/items/index', array(
                'market' => 'all'
            )));
            }
        $categories = \ORM::for_table('category')->where('category_id', 0)->find_array();
        $categories = getCategoryTree($categories);
        $app->render('items/admin_search_import.twig', compact('categories'));
        })->via('GET', 'POST')->name('admin/items/search_import');
    });
// Defined routes for categories admin
$app->group('/admin/categories', $isAdmin($app), function () use ($app) {
    // Define admin categories index
    $app->get('/', function ($page = 1) use ($app) {
        $query = $app->request->get();
        if (isset($query['direction']) && $query['direction'] == 'desc') {
            $categories = \ORM::for_table('category')->order_by_desc('name')->find_array();
            } else {
            $categories = \ORM::for_table('category')->order_by_asc('name')->find_array();
            }
        $categories = getCategoryTree($categories);
        $app->render('categories/admin_index.twig', compact('categories'));
        })->name('admin/categories/index');
    $app->map('/import', function () use ($app) {
        if ($app->request->isPost()) {
            $market = \ORM::for_table('category')->find_one($app->request->post('category_id'));
            $market = $market->as_array();
            if (setMarketCategories($market)) {
                $app->flash('success', sprintf('Categories list of %s imported and updated.', $market['name']));
                } else {
                $app->flash('error', 'Shomething is wrong. Please try again.');
                }
            $app->stash->getItem('navbar/primary')->clear();
            $app->stash->getItem('navbar/secondary')->clear();
            $app->stash->getItem('navbar/categories')->clear();
            $app->redirect($app->urlFor('admin/categories/index'));
            } else {
            $categories = \ORM::for_table('category')->where_equal('category_id', 0)->find_array();
            $app->render('categories/admin_import.twig', compact('categories'));
            }
        })->via('GET', 'POST')->name('admin/categories/import');
    // Define admin categories add section
    $app->map('/add', function () use ($app) {
        if ($app->request->isPost()) {
            $category = \ORM::for_table('category')->create();
            $category->set(array(
                'name' => $app->request->post('name'),
                'description' => $app->request->post('description'),
                'title' => $app->request->post('title'),
                'alias' => URLify::filter($app->request->post('alias')),
                'category_id' => $app->request->post('category_id')
            ));
            if ($category->save()) {
                $app->flash('success', 'New category added successfully.');
                } else {
                $app->flash('error', 'Adding new category failed. Please try again.');
                }
            $app->stash->getItem('navbar/primary')->clear();
            $app->stash->getItem('navbar/secondary')->clear();
            $app->stash->getItem('navbar/categories')->clear();
            $app->redirect($app->urlFor('admin/categories/index'));
            } else {
            $categories = \ORM::for_table('category')->find_array();
            $categories = getCategoryTree($categories);
            $app->render('categories/admin_add.twig', compact('categories'));
            }
        })->via('GET', 'POST')->name('admin/categories/add');
    // Define admin categories edit section
    $app->map('/edit(/:id)', function ($id = null) use ($app) {
        if (!$id) {
            $app->redirect($app->urlFor('admin/categories/index'));
            }
        if ($app->request->isPost()) {
            $category = \ORM::for_table('category')->find_one($id);
            $category->set(array(
                'name' => $app->request->post('name'),
                'description' => $app->request->post('description'),
                'title' => $app->request->post('title'),
                'alias' => URLify::filter($app->request->post('alias')),
                'category_id' => $app->request->post('category_id')
            ));
            if ($category->save()) {
                $app->flash('success', 'Category updated successfully.');
                $app->stash->getItem('navbar/primary')->clear();
                $app->stash->getItem('navbar/secondary')->clear();
                $app->stash->getItem('navbar/categories')->clear();
                $category = $category->as_array();
                if (!$category['category_id']) {
                    $primary_categories = \ORM::for_table('category')->where_equal('category_id', 0)->find_array();
                    $options            = array();
                    foreach ($primary_categories as $value => $pc) {
                        $options[] = array(
                            'value' => $value + 1,
                            'text' => $pc['name']
                        );
                        }
                    \ORM::for_table('setting')->where('name', 'site_markets')->find_result_set()->set('options', json_encode($options))->save();
                    }
                } else {
                $app->flash('error', 'Updating category failed. Please try again.');
                }
            $app->redirect($app->urlFor('admin/categories/index'));
            } else {
            $category   = \ORM::for_table('category')->where('id', $id)->find_one()->as_array();
            $categories = \ORM::for_table('category')->find_array();
            $categories = getCategoryTree($categories);
            $app->render('categories/admin_edit.twig', compact('categories', 'category'));
            }
        })->via('GET', 'POST')->name('admin/categories/edit');
    // Define admin categories delete
    $app->get('/delete(/:id)', function ($id = null) use ($app) {
        if (!$id) {
            $app->redirect($app->urlFor('admin/categories/index'));
            }
        $category = \ORM::for_table('category')->find_one($id);
        if ($category && $category->delete()) {
            $app->flash('success', 'Category deleted successfully.');
            $app->stash->getItem('navbar/primary')->clear();
            $app->stash->getItem('navbar/secondary')->clear();
            $app->stash->getItem('navbar/categories')->clear();
            } else {
            $app->flash('error', 'Delete category failed. Please try again.');
            }
        $app->redirect($app->urlFor('admin/categories/index'));
        })->name('admin/categories/delete');
    $app->get('/csv', function ($action = null) use ($app) {
        if ($app->request->isPost()) {
            if (empty($_FILES['csv']) || $_FILES['csv']['error']) {
                $app->flash('error', 'Import csv failed. Please try again.');
                } else {
                $config = new LexerConfig();
                $config->setDelimiter(";") // Customize delimiter. Default value is comma(,)
                    ->setEnclosure('"') // Customize enclosure. Default value is double quotation(")
                    ->setEscape("\\") // Customize escape character. Default value is backslash(\)
                    ->setToCharset('UTF-8') // Customize target encoding. Default value is null, no converting.
                    ->setFromCharset('SJIS-win') // Customize CSV file encoding. Default value is null.
                    ;
                $lexer              = new Lexer($config);
                $interpreter        = new Interpreter();
                $number_of_imported = 0;
                $interpreter->addObserver(function (array $columns) use (&$number_of_imported) {
                    if (sizeof($columns) == 4) {
                        $category = \ORM::for_table('category')->create();
                        $category->set(array_combine(array(
                            'name',
                            'description',
                            'alias',
                            'category_id'
                        ), $columns));
                        if ($category->save()) {
                            $number_of_imported++;
                            }
                        }
                    });
                $lexer->parse($_FILES['csv']['tmp_name'], $interpreter);
                if ($number_of_imported) {
                    $app->flash('success', $number_of_imported . ' items were imported successfully.');
                    } else {
                    $app->flash('error', 'No items were imported.');
                    }
                }
            $app->redirect($app->urlFor('admin/categories/csv'));
            }
        $app->render('categories/admin_csv.twig');
        })->via('GET', 'POST')->name('admin/categories/csv');
    });
// Defined routes for sources admin
$app->group('/admin/sources', $isAdmin($app), function () use ($app) {
    // Define admin sources index
    $app->get('/', function () use ($app) {
        try {
            //
            $sources = \ORM::for_table('source')->select('source.*')->select('category.name', 'category_name')->select('category.item_count', 'category_count')->join('category', 'category.id = source.category_id');
            //
            $pager   = getPagers(null, $sources->count(), $app->urlFor('admin/sources/index'), $app->config('configs.admin_per_page'));
            \FB::info($pager);
            //
            $sources = $sources->limit($pager['length'])->offset($pager['offset'])->order_by_expr($pager['order'])->find_array();
            } catch (Exception $e) {
            \FB::error($e);
            $sources = array();
            }
        //
        $app->render('sources/admin_index.twig', compact('sources', 'pager'));
        })->name('admin/sources/index');
    // Define admin sources add section
    $app->map('/add', function () use ($app) {
        if ($app->request->isPost()) {
            \FB::log($app->request->post('urls'));
            $urls = explode("\n", $app->request->post('urls'));
            if (empty($urls)) {
                $app->redirect($app->urlFor('admin/sources/index'));
                }
            foreach ($urls as $url) {
                $source = \ORM::for_table('source')->create();
                $source->set(array(
                    'url' => $url,
                    'title' => getFeedTitle($url),
                    'category_id' => $app->request->post('category_id')
                ));
                try {
                    $source->save();
                    $app->flash('success', 'Your source feeds added successfully.');
                    } catch (Exception $e) {
                    \FB::error($e);
                    $app->flash('error', 'Somthing is wrong. Please try again later.');
                    }
                }
            $app->redirect($app->urlFor('admin/sources/index'));
            } else {
            $categories = \ORM::for_table('category')->find_array();
            $categories = getCategoryTree($categories);
            $app->render('sources/admin_add.twig', compact('categories'));
            }
        })->via('GET', 'POST')->name('admin/sources/add');
    //
    $app->map('/edit/:id', function ($id) use ($app) {
        if ($app->request->isPost()) {
            $source = \ORM::for_table('source')->find_one($id);
            $source->set('url', $app->request->post('url'));
            $source->set('title', $app->request->post('title'));
            $source->set('category_id', $app->request->post('category_id'));
            if ($source->save()) {
                $app->flash('success', 'Your source updated successfully.');
                } else {
                $app->flash('error', 'It\'s just dosn\'t saved. Please try again.');
                }
            $app->redirect($app->urlFor('admin/sources/index'));
            } else {
            $source = \ORM::for_table('source')->find_one($id);
            if ($source != null) {
                $source = $source->as_array();
                }
            $categories = \ORM::for_table('category')->find_array();
            $categories = getCategoryTree($categories);
            $app->render('sources/admin_edit.twig', compact('source', 'categories'));
            }
        })->via('GET', 'POST')->name('admin/sources/edit');
    // Define admin sources delete
    $app->get('/delete/:id', function ($id = null) use ($app) {
        $source = \ORM::for_table('source')->find_one($id);
        if ($source && $source->delete()) {
            $app->flash('success', 'Source deleted successfully.');
            } else {
            $app->flash('error', 'Delete source failed. Please try again.');
            }
        $app->redirect($app->urlFor('admin/sources/index'));
        })->name('admin/sources/delete');
    // Define admin sources update
    $app->get('/update/:id', function ($id = null) use ($app) {
        $source = \ORM::for_table('source')->find_one($id);
        $items  = getFeedItems($source);
        $count  = 0;
        foreach ($items as $item) {
            try {
                $item = array_merge($item, getEnvatoDetail($item['id']));
                $tags = $item['tags'];
                unset($item['tags']);
                if (setItemData($item)) {
                    $count++;
                    setItemTags($item['id'], $tags);
                    setItemAuthor($item['user']);
                    }
                } catch (\Exception $e) {
                \FB::error($e);
                continue;
                }
            }
        $updated = \ORM::for_table('source')->where('id', $source['id'])->find_result_set()->set('last_update', date('Y-m-d H:i:s'))->save();
        if ($updated) {
            $app->flash('success', sprintf('Source updated successfully and %d items imported.', $count));
            } else {
            $app->flash('error', 'Update source failed. Please try again.');
            }
        $app->redirect($app->urlFor('admin/sources/index'));
        })->name('admin/sources/update');
    });
// Defined routes for settings admin
$app->group('/admin/settings', $isAdmin($app), function () use ($app) {
    // Define admin settings index
    $app->get('/', function () use ($app) {
        $settings = \ORM::for_table('setting')->find_array();
        $app->render('settings/admin_index.twig', compact('settings'));
        })->name('admin/settings/index');
    // Define admin settings edit section
    $app->map('/edit(/:id)', function ($id = null) use ($app) {
        if (empty($id)) {
            }
        if ($app->request->isPost()) {
            $value   = $app->request->post('value');
            $setting = \ORM::for_table('setting')->find_one($id);
            if (!is_array($app->request->put('value'))) {
                $setting->set('value', $value);
                } else {
                if (!isset($value['file'])) {
                    $setting->set('value', json_encode($value));
                    } else {
                    if ($value['file'] == '@delete') {
                        @unlink(realpath('cache') . '/' . $setting->get('value'));
                        $setting->set('value', '');
                        } else {
                        $fileSystem  = new \Upload\Storage\FileSystem(realpath('cache'));
                        $fileRequest = new \Upload\File('file', $fileSystem);
                        $fileNewName = uniqid();
                        $fileRequest->setName($fileNewName);
                        $fileRequest->addValidations(array(
                            new \Upload\Validation\Mimetype(array(
                                'image/png',
                                'image/gif',
                                'image/jpeg'
                            )),
                            new \Upload\Validation\Size('1M')
                        ));
                        try {
                            $fileRequest->upload();
                            $value = $fileRequest->getNameWithExtension();
                            $setting->set('value', $value);
                            } catch (\Exception $e) {
                            \FB::error($fileRequest->getErrors());
                            }
                        }
                    }
                }
            if ($setting->save()) {
                $app->stash->getItem('configs')->clear();
                }
            } else {
            $setting = \ORM::for_table('setting')->find_one($id)->as_array();
            $app->render('settings/admin_edit.twig', compact('setting'));
            }
        })->via('GET', 'POST')->name('admin/settings/edit');
    //
    $app->put('/edit', function () use ($app) {
        if ($app->request->isPut()) {
            \FB::log($app->request->put());
            $setting = \ORM::for_table('setting')->find_one($app->request->put('pk'));
            if (!is_array($app->request->put('value'))) {
                $setting->set('value', $app->request->put('value'));
                } else {
                $setting->set('value', json_encode($app->request->put('value')));
                }
            $app->stash->getItem('configs')->clear();

            return $setting->save();
            }
        });
    });
// Defined routes for users admin
$app->group('/admin/users', $isAdmin($app), function () use ($app) {
    // Define admin users info edit
    $app->map('/', function () use ($app) {
        if ($app->request->isPost()) {
            $user = \ORM::for_table('user')->where_equal('username', $_SESSION['user']['username'])->find_one();
            $user->set('email', $app->request->post('email'));
            $user->set('username', $app->request->post('username'));
            $password = $app->request->post('password');
            $passconf = $app->request->post('passconf');
            if (!empty($password)) {
                if ($password != $passconf) {
                    $app->flash('error', 'Password fields are not the same.');
                    $app->redirect($app->urlFor('admin/users/index'));
                    } else {
                    $user->set('password', md5($app->request->post('password')));
                    }
                }
            if ($user->save()) {
                $_SESSION['user']['username'] = $user['username'];
                $app->flash('success', 'Your account informations updated.');
                } else {
                $app->flash('error', 'Updatig your information failed. Please try again.');
                }
            $app->redirect($app->urlFor('admin/users/index'));
            } else {
            $user = \ORM::for_table('user')->where('username', $_SESSION['user']['username'])->find_one()->as_array();
            $app->render('users/admin_edit.twig', compact('user'));
            }
        })->via('GET', 'POST')->name('admin/users/index');
    });
// Defined routes for adverts admin
$app->group('/admin/adverts', $isAdmin($app), function () use ($app) {
    $app->get('/', function () use ($app) {
        $adverts = \ORM::for_table('advert')->select('advert.*');
        $pager   = getPagers(null, $adverts->count(), '/admin/adverts', $app->config('configs.admin_per_page'));
        $adverts = $adverts->limit($pager['length'])->offset($pager['offset'])->order_by_expr($pager['order'])->find_array();
        $app->render('adverts/admin_index.twig', compact('adverts', 'pager'));
        })->name('admin/adverts/index');
    $app->map('/add', function () use ($app) {
        if ($app->request->isPost()) {
            $advert = \ORM::for_table('advert')->create();
            $advert->set('title', $app->request->post('title'));
            $advert->set('content', $app->request->post('content'));
            if ($advert->save()) {
                $app->flash('success', 'Your advert saved successfully.');
                $app->stash->getItem('sidebar/adverts')->clear();
                } else {
                $app->flash('error', 'It\'s just doesn\'t saved. Please try again.');
                }
            $app->redirect($app->urlFor('admin/adverts/index'));
            } else {
            $app->render('adverts/admin_add.twig');
            }
        })->via('GET', 'POST')->name('admin/adverts/add');
    $app->map('/edit/:id', function ($id) use ($app) {
        if ($app->request->isPost()) {
            $advert = \ORM::for_table('advert')->find_one($id);
            $advert->set('title', $app->request->post('title'));
            $advert->set('content', $app->request->post('content'));
            if ($advert->save()) {
                $app->flash('success', 'Your advert updated successfully.');
                $app->stash->getItem('sidebar/adverts')->clear();
                } else {
                $app->flash('error', 'It\'s just dosn\'t saved. Please try again.');
                }
            $app->redirect($app->urlFor('admin/adverts/index'));
            } else {
            $advert = \ORM::for_table('advert')->find_one($id);
            if ($advert) {
                $advert = $advert->as_array();
                }
            $app->render('adverts/admin_edit.twig', compact('advert'));
            }
        })->via('GET', 'POST')->name('admin/adverts/edit');
    $app->get('/delete/:id', function ($id) use ($app) {
        $advert = \ORM::for_table('advert')->find_one($id);
        if ($advert && $advert->delete()) {
            $app->flash('success', 'Advert deleted successfully.');
            } else {
            $app->flash('error', 'Deleting advert failed. Please try again.');
            }
        $app->redirect($app->urlFor('admin/adverts/index'));
        })->name('/admin/adverts/delete');
    });
// Define routes for admin blog posts
$app->group('/admin/posts', $isAdmin($app), function () use ($app) {
    // Defined posts index section
    $app->get('/', function () use ($app) {
        $posts = \ORM::for_table('post');
        $pager = getPagers(null, $posts->count(), '/admin/posts', $app->config('configs.admin_per_page'));
        $posts = $posts->limit($pager['length'])->offset($pager['offset'])->order_by_expr($pager['order'])->find_array();
        $app->render('posts/admin_index.twig', compact('posts', 'pager'));
        })->name('admin/posts/index');
    // Defined posts add section
    $app->map('/add', function () use ($app) {
        if ($app->request->isPost()) {
            $data = $app->request->post();
            if (empty($data['alias']) && empty($data['title'])) {
                $app->flash('error', 'Page name and title fields could not be empty.');
                $app->redirect($app->urlFor('admin/posts/add'));
                }
            $post = \ORM::for_table('post')->create();
            $post->set(array(
                'title' => $data['title'],
                'alias' => URLify::filter($data['alias']),
                'text' => $data['text'],
                'more' => $data['more'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ));
            if ($post->save() === false) {
                $app->flash('error', 'Saving post operation failed. Please try again.');
                } else {
                $app->flash('success', 'Your post has been saved successfully.');
                }
            $app->redirect($app->urlFor('admin/posts/index'));
            } else {
            $app->render('posts/admin_add.twig');
            }
        })->via('GET', 'POST')->name('admin/posts/add');
    // Define admin posts edit section
    $app->map('/edit/:id', function ($id) use ($app) {
        if ($app->request->isPost()) {
            $data = $app->request->post();
            if (empty($data['alias']) && empty($data['title'])) {
                $app->flash('error', 'Page name and title fields could not be empty.');
                $app->redirect($app->urlFor('admin/posts/add'));
                }
            $post = \ORM::for_table('post')->where('id', $id)->find_one();
            $post->set(array(
                'title' => $data['title'],
                'alias' => URLify::filter($data['alias']),
                'text' => $data['text'],
                'more' => $data['more'],
                'updated_at' => date('Y-m-d H:i:s')
            ));
            if ($post->save()) {
                $app->flash('success', 'The post updated successfully');
                } else {
                $app->flash('error', 'Updating post failed. Please try again.');
                }
            $app->redirect($app->urlFor('admin/posts/index'));
            } else {
            $post = \ORM::for_table('post')->where('id', $id)->find_one()->as_array();
            $app->render('posts/admin_edit.twig', compact('post'));
            }
        })->via('GET', 'POST')->name('admin/posts/edit')->conditions(array(
        'id' => '\d+'
    ));
    // Define admin posts delete
    $app->get('/delete/:id', function ($id) use ($app) {
        if (\ORM::for_table('post')->find_one($id)->delete()) {
            $app->flash('success', 'The post has been deleted successfully.');
            } else {
            $app->flash('error', 'Deleting post failed. Please try again.');
            }
        $app->redirect($app->urlFor('admin/posts/index'));
        })->name('admin/posts/delete');
    });
// Defined routes for authors admin
$app->group('/admin/authors', $isAdmin($app), function () use ($app) {
    //
    $app->get('/', function () use ($app) {
        $authors = \ORM::for_table('author');
        $pager   = getPagerAuthor(null, $authors->count(), '/admin/authors', $app->config('configs.admin_per_page'));
        $authors = $authors->limit($pager['length'])->offset($pager['offset'])->order_by_expr($pager['order'])->group_by("username")->find_array();
        $app->render('authors/admin_index.twig', compact('authors', 'pager'));
        })->via('GET')->name('admin/authors/index');
    //
    $app->map('/add', function () use ($app) {
        if ($app->request->isPost()) {
            $author = \ORM::for_table('author')->create();
            $author->set('username', $app->request->post('username'));
            $author->set('country', $app->request->post('country'));
            $author->set('sales', $app->request->post('sales'));
            $author->set('location', $app->request->post('location'));
            $author->set('image', $app->request->post('image'));
            $author->set('followers', $app->request->post('followers'));
            if ($author->save()) {
                $app->flash('success', 'Your author saved successfully.');
                $app->stash->getItem('sidebar/authors')->clear();
                } else {
                $app->flash('error', 'It\'s just doesn\'t saved. Please try again.');
                }
            $app->redirect($app->urlFor('admin/authors/index'));
            } else {
            $app->render('authors/admin_add.twig');
            }
        })->via('GET', 'POST')->name('admin/authors/add');
    $app->map('/edit/:id', function ($id) use ($app) {
        if ($app->request->isPost()) {
            $author = \ORM::for_table('author')->find_one($id);
            $author->set('username', $app->request->post('username'));
            $author->set('country', $app->request->post('country'));
            $author->set('sales', $app->request->post('sales'));
            $author->set('location', $app->request->post('location'));
            $author->set('image', $app->request->post('image'));
            $author->set('followers', $app->request->post('followers'));
            if ($author->save()) {
                $app->flash('success', 'Your author updated successfully.');
                $app->stash->getItem('sidebar/authors')->clear();
                } else {
                $app->flash('error', 'It\'s just dosn\'t saved. Please try again.');
                }
            $app->redirect($app->urlFor('admin/authors/index'));
            } else {
            $author = \ORM::for_table('author')->find_one($id);
            if ($author) {
                $author = $author->as_array();
                }
            $app->render('authors/admin_edit.twig', compact('author'));
            }
        })->via('GET', 'POST')->name('admin/authors/edit');
    $app->get('/delete/:id', function ($id) use ($app) {
        $author = \ORM::for_table('author')->find_one($id);
        if ($author && $author->delete()) {
            $app->flash('success', 'Author deleted successfully.');
            } else {
            $app->flash('error', 'Deleting author failed. Please try again.');
            }
        $app->redirect($app->urlFor('admin/authors/index'));
        })->name('/admin/authors/delete');
    $app->get('/update/:id', function ($id) use ($app) {
        $author     = \ORM::for_table('author')->find_one($id);
        $authorInfo = $author->as_array();
        $data       = $app->envato->public_user_data($authorInfo['username']);
        $author->set('sales', $data->sales);
        $author->set('followers', $data->followers);
        if ($author->save()) {
            $app->flash('success', 'Author updated successfuly.');
            $app->stash->getItem('sidebar/authors')->clear();
            } else {
            $app->flash('error', 'It\'s just doesn\'t updated. Please try again.');
            }
        $app->redirect($app->urlFor('admin/authors/index'));
        })->name('/admin/authors/update');
    // Feature author
    $app->get('/featured/:id', function ($id) use ($app) {
        $author = \ORM::for_table('author')->find_one($id);
        $author->set('featured', 1);
        if ($author->save()) {
            $app->flash('success', 'Author featured successfuly.');
            } else {
            $app->flash('error', 'It\'s just doesn\'t featured. Please try again.');
            }
        $app->redirect($app->urlFor('admin/authors/index'));
        })->name('/admin/authors/featured');
    // Unfeature author
    $app->get('/unfeatured/:id', function ($id) use ($app) {
        $author = \ORM::for_table('author')->find_one($id);
        $author->set('featured', 0);
        if ($author->save()) {
            $app->flash('success', 'Author unfeatured successfuly.');
            } else {
            $app->flash('error', 'It\'s just doesn\'t unfeatured. Please try again.');
            }
        $app->redirect($app->urlFor('admin/authors/index'));
        })->name('/admin/authors/unfeatured');
    });
// Defined routes for author sections
$app->group('/authors', function () use ($app) {
    // Defined route for authors list page
    $app->get('/', function () use ($app) {
        $authors = \ORM::for_table('author')->order_by_desc('featured');
        $pager   = getPagerAuthor(null, $authors->count(), '/authors', $app->config('configs.products_per_page'));
        $authors = $authors->limit($pager['length'])->offset($pager['offset'])->order_by_expr($pager['order'])->find_array();
        $app->render('authors/index.twig', compact('authors', 'pager'));
        });
    // Define route for authors items index
    $app->get('/:user', function ($user) use ($app) {
        //sync
        $data   = $app->envato->public_user_data($user);
        $author = \ORM::for_table('author')->where('username', $user)->find_one();
        $author->set('username', $data->username);
        $author->set('sales', $data->sales);
        $author->set('followers', $data->followers);
        $author->set('image', $data->image);
        $author->save();
        $items = \ORM::for_table('item')->select('item.*')->select('category.name', 'category_name')->join('category', 'category.id = item.category_id')->where('user', $user);
        $route = $app->urlFor('authors/items', array(
            'user' => $user
        ));
        $pager = getPager(null, $items->count(), $route, $app->config('configs.products_per_page'));
        try {
            $items = $items->limit($pager['length'])->offset($pager['offset'])->order_by_expr($pager['order'])->find_array();
            } catch (Exception $e) {
            \FB::error($e);
            $items = array();
            }
        $markets = \ORM::for_table('category')->where('category_id', 0)->find_array();
        $app->render('items/index.twig', compact('items', 'pager', 'markets', 'user'));
        })->name('authors/items');
    });
// Defined contacts section route
$app->group('/contacts', function () use ($app) {
    if (!session_id()) {
        session_start();
        }
    // Defined contacts page route
    $app->map('/', function () use ($app) {
        if ($app->request->isPost()) {
            $name  = $app->request->post('name');
            $valid = \Respect\Validation\Validator::string()->notEmpty()->validate($name);
            if ($valid == false) {
                $app->flash('error', 'Your name field could not be empty.');
                $app->redirect($app->urlFor('contacts/index'));
                }
            $email = $app->request->post('email');
            $valid = \Respect\Validation\Validator::email()->validate($email);
            if ($valid == false) {
                $app->flash('error', 'Your email adress should be valid.');
                $app->redirect($app->urlFor('contacts/index'));
                }
            $subject = $app->request->post('subject');
            $valid   = \Respect\Validation\Validator::string()->notEmpty()->validate($subject);
            if ($valid == false) {
                $app->flash('error', 'Your subject field could not be empty.');
                $app->redirect($app->urlFor('contacts/index'));
                }
            $message = $app->request->post('message');
            $valid   = \Respect\Validation\Validator::string()->notEmpty()->validate($message);
            if ($valid == false) {
                $app->flash('error', 'Your message field could not be empty.');
                $app->redirect($app->urlFor('contacts/index'));
                }
            $captcha = $app->request->post('captcha');
            $valid   = \Respect\Validation\Validator::string()->notEmpty()->validate($captcha);
            if ($valid == false) {
                $app->flash('error', 'Your captcha field could not be empty.');
                $app->redirect($app->urlFor('contacts/index'));
                }
            if ($captcha != $_SESSION['phrase']) {
                $app->flash('error', 'Invalid captcha !');
                $app->redirect($app->urlFor('contacts/index'));
                }
            $captcha = $app->request->post('captcha');
            $builder = new \Gregwar\Captcha\CaptchaBuilder;
            //
            $mail    = new \PHPMailer();
            if ($app->config('configs.site_smtp_status')) {
                $mail->isSMTP();
                $mail->Host       = $app->config('configs.site_smtp_hostname');
                $mail->SMTPAuth   = true;
                $mail->Username   = $app->config('configs.site_smtp_username');
                $mail->Password   = $app->config('configs.site_smtp_password');
                $mail->SMTPSecure = 'tls';
                }
            $mail->setFrom($email, $name);
            $mail->addAddress($app->config('configs.contacts_email'));
            $mail->Subject = $subject;
            $mail->msgHTML(strip_tags($message));
            if ($mail->send()) {
                $app->flash('success', 'Your message sent successfully.');
                } else {
                $app->flash('error', 'Something is wrong. Please try again.');
                }
            $app->redirect($app->urlFor('contacts/index'));
            }
        $app->render('contacts/index.twig');
        })->via('GET', 'POST')->name('contacts/index');
    //
    $app->get('/captcha', function () use ($app) {
        $builder = new \Gregwar\Captcha\CaptchaBuilder;
        $builder->setBackgroundColor(255, 255, 255);
        $builder->build(200, 80);
        $_SESSION['phrase'] = $builder->getPhrase();
        $builder->output();
        })->name('contacts/captcha');
    });
// Defined routes for blog
$app->group('/blog', function () use ($app) {
    // Defined posts index section
    $app->get('/', function () use ($app) {
        $posts = \ORM::for_table('post');
        $route = $app->urlFor('blog/posts/index');
        $pager = getPagers(null, $posts->count(), $route, 5);
        $posts = $posts->limit($pager['length'])->offset($pager['offset'])->order_by_expr($pager['order'])->find_array();
        $app->render('posts/index.twig', compact('posts', 'pager'));
        })->name('blog/posts/index');
    // Define posts individual view section
    $app->map('/:alias', function ($alias = null) use ($app) {
        // Retrieve post by using alias
        $post = \ORM::for_table('post')->where('alias', $alias)->find_one();
        // Return not found if post not exist
        if ($post == null) {
            $app->notFound();

            return;
            }
        // Convert post object to an array
        $post = $post->as_array();
        // Render post data using twig template
        $app->render('posts/view.twig', compact('post'));
        })->via('GET', 'POST')->name('blog/posts/view');
    });
$app->get('/cleanup', function () use ($app) {
    });
// Run app
$app->run();
