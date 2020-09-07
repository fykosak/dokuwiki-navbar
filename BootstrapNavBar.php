<?php

namespace FYKOS\dokuwiki\template\NavBar;

use dokuwiki\Menu\AbstractMenu;
use dokuwiki\Menu\PageMenu;
use dokuwiki\Menu\SiteMenu;
use dokuwiki\Menu\UserMenu;

class BootstrapNavBar {

    private array $data = [];

    private ?string $brand = null;

    private string $className;

    private string $id;

    public function __construct(string $id, string $className) {
        $this->id = $id;
        $this->className = $className;
    }

    public function addTools(?string $class = '', bool $allowLoggedInOnly = false): void {
        global $INFO;
        global $lang;
        if ($allowLoggedInOnly && !$INFO['userinfo']['name']) {
            return;
        }
        $userName = (($INFO['userinfo']['name'] != null) ? ($lang['loggedinas'] .
            $INFO['userinfo']['name']) : tpl_getLang('nologin'));

        $this->data[] = [
            'class' => 'nav ' . $class,
            'data' => [
                new NavBarItem(null, '<span class="nav-item fa fa-cogs"></span>', 1, null),
                new NavBarItem(null, '<div class="dropdown-item"><span class="fa fa-user"></span>' . $userName . '</div>', 2, null),
                ... $this->addUserMenu(),
                ... $this->addPageMenu(),
                ... $this->addSiteMenu(),
            ],
        ];
    }

    public function addBrand(string $href = '', ?string $text = null, ?string $imageSrc = null): void {
        $html = '<a class="navbar-brand" href="' . wl(cleanID($href)) . '">';
        if ($imageSrc) {
            $html .= '<img src="' . tpl_basedir() . $imageSrc .
                '" width="30" height="30" class="d-inline-block align-top" alt="">';
        }
        if ($text) {
            $html .= $text;
        }
        $html .= '</a>';
        $this->brand = $html;
    }


    public function render(): string {
        $html = '<nav class="navbar navbar-toggleable-md ' . $this->className . '">';
        if ($this->brand) {
            $html .= $this->brand;
        }
        $html .= '
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="' . '#mainNavbar' . $this->id . '"
    aria-controls="navbarSupportedContent"
    aria-expanded="false"
    aria-label="Toggle navigation">';

        $html .= '<span class="navbar-toggler-icon"></span>';
        $html .= '</button>
         <div class="collapse navbar-collapse" id="mainNavbar' . $this->id . '">';
        foreach ($this->data as $item) {
            $html .= $this->renderItem($item['data'], $item['class']);
        }
        $html .= '</div>';
        $html .= '</nav>';
        return $html;
    }

    /**
     * @param $filename string
     * @return NavBarItem[]
     */
    private function parseMenuFile(string $filename): array {
        $filePath = wikiFN($filename);
        $data = [];
        if (file_exists($filePath)) {
            $lines = array_filter(file($filePath),
                function ($line) {
                    return preg_match('/^\s+\*/', $line);
                });

            $numLines = count($lines);
            for ($i = 0; $i < $numLines; $i++) {
                if (!$lines[$i]) {
                    continue;
                }
                [$prefix, $content] = explode('*', $lines[$i]);
                $level = (int)strlen($prefix) / 2;
                $level = ($level > 2) ? 2 : $level;

                if (!preg_match('/\s*\[\[[^\]]+\]\]/', $content)) {
                    continue;
                }
                $content = str_replace([']', '['], '', trim($content));
                [$id, $content, $icon] = explode('|', $content);
                $data[] = new NavBarItem($id, $content, $level, $icon);
            }
        }
        return $data;
    }

    public function addMenuText(string $file, ?string $class = null): void {
        global $conf;
        $pageLang = $conf['lang'];
        $menuFileName = 'system/' . $file . '_' . $pageLang;

        $this->data[] = [
            'class' => 'nav ' . $class ?? '',
            'data' => $this->parseMenuFile($menuFileName),
        ];
    }

    public function addLangSelect(?string $class = null): void {
        global $conf;
        $data = [];
        if (!isset($conf['available_lang']) || !is_countable($conf['available_lang']) || !count($conf['available_lang'])) {
            return;
        }
        $data[] = new NavBarItem(null, '<span class="fa fa-language"></span>', 1, null);

        foreach ($conf['available_lang'] as $currentLang) {
            $data[] = new NavBarItem(null, '<a
                href="' . $currentLang['content']['url'] . '"
                class="dropdown-item ' . $currentLang['content']['class'] . ' ' .
                ($currentLang['code'] == $conf['lang'] ? 'active' : '') . '"
                ' . $currentLang['content']['more'] . '
                >' . $currentLang['content']['text'] . ' </a> ', 2, null);
        }
        $this->data[] = [
            'class' => 'nav ' . $class ?? '',
            'data' => $data,
        ];
    }

    /**
     * @return NavBarItem[]
     */
    private function addUserMenu(): array {
        global $lang;
        $data = [];
        $data[] = new NavBarItem(null, '<div class="dropdown-header" ><span class="glyphicon glyphicon-user" ></span> ' .
            $lang['user_tools'] . ' .</div>', 2, null);
        return [...$data, ...$this->prepareMenuItems(new UserMenu())];
    }

    /**
     * @return NavBarItem[]
     */
    private function addSiteMenu(): array {
        global $lang;
        $data = [];
        $data[] = new NavBarItem(null, '<div class="dropdown-header" ><span class="glyphicon glyphicon-user" ></span> ' .
            $lang['site_tools'] . ' .</div>', 2, null);
        return [...$data, ...$this->prepareMenuItems(new SiteMenu())];
    }

    /**
     * @return NavBarItem[]
     */
    private function addPageMenu(): array {
        global $lang;
        $data = [];
        $data[] = new NavBarItem(null, '<div class="dropdown-header"><span class="fa fa-user-o"></span>' . $lang['page_tools'] . '</div>', 2, null);
        return [...$data, ...$this->prepareMenuItems(new PageMenu())];
    }

    private function prepareMenuItems(AbstractMenu $menu): array {
        $data = [];
        foreach ($menu->getItems() as $item) {
            $data[] = new NavBarItem(null, $item->asHtmlLink('dropdown-item '), 2, null);
        }
        return $data;
    }

    /**
     * @param $data NavBarItem[]
     * @param string $class
     * @return string
     */
    private function renderItem(array $data, string $class): string {
        $inLI = false;
        $inUL = false;

        $html = ' <div class="nav navbar-nav ' . $class . '" > ';

        foreach ($data as $k => $item) {
            $link = $item->getLink();
            $title = $item->getIcon() . $item->getContent();
            if ($item->getLevel() == 1) {
                if ($inUL) {
                    $inUL = false;
                    $html .= '</div>';
                }
                if ($inLI) {
                    $inLI = false;
                    $html .= '</div>';
                }
                /* is next level 2? */
                if ($data[$k + 1] && $data[$k + 1]->getLevel() == 2) {
                    $inLI = true;
                    $html .= '<div class="dropdown nav-item"><a href="' . $link .
                        '" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" >' .
                        $title . '<span class="caret"></span></a>';
                } else {
                    $html .= '<a class="nav-item nav-link" href="' . $link . '">' . $title . '</a>';
                }
            } elseif ($item->getLevel() == 2) {
                if (!$inUL) {
                    $inUL = true;
                    $html .= '<div class="dropdown-menu" role="menu">' . "\n";
                }

                if ($item->hasId()) {
                    $html .= '<a class="dropdown-item" href="' . $link . '">' . $title . '</a>';
                } else {
                    $html .= $title;
                }
            }
        }
        if ($inUL) {
            $html .= '</div>';
        }
        if ($inLI) {
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}
