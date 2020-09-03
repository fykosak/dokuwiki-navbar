<?php

namespace FYKOS\dokuwiki\template\NavBar;

class NavBarItem {

    private ?string $pageId;
    private ?string $icon;
    private int $level;
    private ?string $content;

    public function __construct(?string $pageId, ?string $content, int $level, ?string $icon) {
        $this->pageId = $pageId;
        $this->icon = $icon;
        $this->level = $level;
        $this->content = $content;
    }

    public function getId(): ?string {
        return $this->pageId;
    }

    public function getIcon(): string {
        if (isset($this->icon)) {
            return ' <span class="' . $this->icon . '" ></span > ';
        }
        return '';
    }

    private function isExternal(): bool {
        return preg_match('#https?://#', $this->pageId);
    }

    public function getLevel(): int {
        return $this->level;
    }

    public function getContent(): ?string {
        return $this->content;
    }

    public function getLink(): string {
        if ($this->isExternal()) {
            return htmlspecialchars($this->pageId);
        }
        return wl(cleanID($this->pageId));
    }

    public function hasId(): bool {
        return !is_null($this->pageId);
    }
}
