<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 2/24/2015
 * Time: 2:51 PM
 */
namespace Site\Path\HTML;

use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\IRequest;
use CPath\Request\Session\ISessionRequest;
use Site\Path\CreatePath;
use Site\Path\DB\PathEntry;
use Site\Path\ManagePath;
use Site\Render\PopUpBox\HTMLPopUpBox;

class HTMLPathTip extends HTMLPopUpBox
{
    /** @var PathEntry */
    private $SubPath = null;
    private $fullPath = null;

    public function __construct(IRequest $Request, $relativePath, $tipContent=null) {
        $this->SubPath = PathEntry::trySubPath($Request, $relativePath);
        $this->fullPath = $Request->getPath() . $relativePath;
        parent::__construct($tipContent);
    }

    function renderContent(IRequest $Request, IRenderHTML $Parent = null) {
        $isSession = false;
        if($Request instanceof ISessionRequest && $Request->getSessionID())
            $isSession = true;

        if($this->SubPath) {
            echo RI::ni(2), "<b>", $this->SubPath->getTitle(), "</b><br/><br/>";
            echo RI::ni(2), $this->SubPath->getContent();
            if($isSession)
                echo RI::ni(2), "<br/><a class='edit' href='", $Request->getDomainPath(), ltrim(ManagePath::getRequestURL($this->fullPath), '/'), "'>Edit content</a>";

        } else {
            parent::renderContent($Request);
            if($isSession)
                echo RI::ni(2), "<br/><a class='add' href='", $Request->getDomainPath(), ltrim(CreatePath::getRequestURL($this->fullPath), '/'), "'>Expand content</a>";
        }
    }


}