<?php

namespace FYKOS\dokuwiki\template\NavBar;

use dokuwiki\Menu\AbstractMenu;
use dokuwiki\Menu\PageMenu;
use dokuwiki\Menu\SiteMenu;
use dokuwiki\Menu\UserMenu;

class BootstrapNavBar {

    private array $data = [];

    private ?string $brand = null;

    private string $html = '';

    private string $className;

    private string $id;

    public function __construct(string $id) {
        $this->id = $id;
    }

    public function setClassName(string $className): self {
        $this->className = $className;
        return $this;
    }

    public function addTools(?string $class = '', bool $allowLoggedInOnly = false): self {
        global $INFO;
        global $lang;
        if ($allowLoggedInOnly && !$INFO['userinfo']['name']) {
            return $this;
        }
        $data = [];
        $data[] = new NavBarItem(null, '<span class="nav-item fa fa-cogs"></span>', 1, null);

        $userName = (($INFO['userinfo']['name'] != null) ? ($lang['loggedinas'] .
            $INFO['userinfo']['name']) : tpl_getLang('nologin'));
        $data[] = new NavBarItem(null, '<div class="dropdown-item"><span class="fa fa-user"></span>' . $userName . '</div>', 2, null);
        $data = array_merge($data, $this->getUserTools(), $this->getPageTools(), $this->getSiteTools());
        $this->data[] = [
            'class' => 'nav ' . $class,
            'data' => $data,
        ];
        return $this;
    }

    public function addBrand(string $href = '', ?string $text = null, ?string $imageSrc = null): self {
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
        return $this;
    }

    /**
     * @return $this
     */
    public function mainMenu(): self {
        $this->html .= '<nav class="navbar navbar-toggleable-md ' . $this->className . '">';
        if ($this->brand) {
            $this->html .= $this->brand;
        }
        $this->html .= '
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="' . '#mainNavbar' . $this->id . '"
    aria-controls="navbarSupportedContent"
    aria-expanded="false"
    aria-label="Toggle navigation">';

        $this->html .= '<span class="navbar-toggler-icon"></span>';
        $this->html .= '</button>
         <div class="collapse navbar-collapse" id="mainNavbar' . $this->id . '">';
        foreach ($this->data as $item) {
            $this->renderNavBar($item['data'], $item['class']);
        }
        $this->html .= '</div>';
        $this->html .= '</nav>';
        return $this;
    }

    public function render(): self {
        $this->mainMenu();
        echo $this->html;
        return $this;
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

    public function addMenuText(string $file, ?string $class = null): self {
        global $conf;
        $pageLang = $conf['lang'];
        $menuFileName = 'system/' . $file . '_' . $pageLang;

        $this->data[] = [
            'class' => 'nav ' . $class ?? '',
            'data' => $this->parseMenuFile($menuFileName),
        ];
        return $this;
    }

    public function addLangSelect(?string $class = null): self {
        global $conf;
        $data = [];
        if (count($conf['available_lang']) == 0) return $this;
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
        return $this;
    }

    /**
     * @return NavBarItem[]
     */
    private function getUserTools(): array {
        global $lang;
        $data = [];
        $data[] = new NavBarItem(null, '<div class="dropdown-header" ><span class="glyphicon glyphicon-user" ></span> ' .
            $lang['user_tools'] . ' .</div>', 2, null);
        return [...$data, ...$this->prepareMenuItems(new UserMenu())];
    }

    /**
     * @return NavBarItem[]
     */
    private function getSiteTools(): array {
        global $lang;
        $data = [];
        $data[] = new NavBarItem(null, '<div class="dropdown-header" ><span class="glyphicon glyphicon-user" ></span> ' .
            $lang['site_tools'] . ' .</div>', 2, null);
        return [...$data, ...$this->prepareMenuItems(new SiteMenu())];
    }

    /**
     * @return NavBarItem[]
     */
    private function getPageTools(): array {
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
     */
    private function renderNavBar(array $data, string $class): void {
        $inLI = false;
        $inUL = false;

        $this->html .= ' <div class="nav navbar-nav ' . $class . '" > ';

        foreach ($data as $k => $item) {
            $link = $item->getLink();
            $title = $item->getIcon() . $item->getContent();
            if ($item->getLevel() == 1) {
                if ($inUL) {
                    $inUL = false;
                    $this->html .= '</div>';
                }
                if ($inLI) {
                    $inLI = false;
                    $this->html .= '</div>';
                }
                /* is next level 2? */
                if ($data[$k + 1] && $data[$k + 1]->getLevel() == 2) {
                    $inLI = true;
                    $this->html .= '<div class="dropdown nav-item"><a href="' . $link .
                        '" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" >' .
                        $title . '<span class="caret"></span></a>';
                } else {
                    $this->html .= '<a class="nav-item nav-link" href="' . $link . '">' . $title . '</a>';
                }
            } elseif ($item->getLevel() == 2) {
                if (!$inUL) {
                    $inUL = true;
                    $this->html .= '<div class="dropdown-menu" role="menu">' . "\n";
                }

                if ($item->hasId()) {
                    $this->html .= '<a class="dropdown-item" href="' . $link . '">' . $title . '</a>';
                } else {
                    $this->html .= $title;
                }
            }
        }
        if ($inUL) {
            $this->html .= '</div>';
        }
        if ($inLI) {
            $this->html .= '</div>';
        }
        $this->html .= '</div>';
    }
}
