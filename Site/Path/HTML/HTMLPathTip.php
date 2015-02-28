<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 2/24/2015
 * Time: 2:51 PM
 */
namespace Site\Path\HTML;

use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\Header\IHeaderWriter;
use CPath\Render\HTML\Header\IHTMLSupportHeaders;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\IRequest;
use CPath\Request\Session\ISessionRequest;
use Site\Account\DB\AccountEntry;
use Site\Path\CreatePath;
use Site\Path\DB\PathEntry;
use Site\Path\IProcessSubPaths;
use Site\Path\ManagePath;
use Site\Song\Review\DB\SongReviewEntry;

class HTMLPathTip implements IRenderHTML, IHTMLSupportHeaders
{
    /** @var PathEntry */
    private $SubPath = null;
    private $fullPath = null;

    public function __construct(IRequest $Request, $relativePath, $tipContent=null) {
        $this->SubPath = PathEntry::trySubPath($Request, $relativePath);
        $this->fullPath = $Request->getPath() . $relativePath;
        $this->content = $tipContent;
    }

    /**
     * Render request as html
     * @param IRequest $Request the IRequest inst for this render which contains the request and remaining args
     * @param IAttributes $Attr
     * @param IRenderHTML $Parent
     * @return String|void always returns void
     */
    function renderHTML(IRequest $Request, IAttributes $Attr = null, IRenderHTML $Parent = null) {
        echo RI::ni(), "<div class='path-tip'>";

        $isSession = false;
        if($Request instanceof ISessionRequest && $Request->hasSessionCookie())
            $isSession = true;

        echo RI::ni(1), "<div class='path-tip-content'>";

        if($this->SubPath) {
            echo RI::ni(2), "<div class='path-tip-title'>";
            echo RI::ni(3), $this->SubPath->getTitle();
            echo RI::ni(2), "</div>";
            echo RI::ni(2), $this->SubPath->getContent();
            if($isSession)
                echo RI::ni(2), "<br/><a class='edit' href='", $Request->getDomainPath(), ltrim(ManagePath::getRequestURL($this->fullPath), '/'), "'>Edit</a>";
            echo RI::ni(1), "</div>";

        } else {
            if($this->content) {
                echo RI::ni(2), "<div class='path-tip-default-content'>", $this->content, "</div>";
                if($isSession)
                    echo RI::ni(2), "<br/><a class='add' href='", $Request->getDomainPath(), ltrim(CreatePath::getRequestURL($this->fullPath), '/'), "'>Expand content</a>";

            } else {
                echo RI::ni(2), "<div class='path-tip-title'>No path tip content has been added yet</div>";
                if($isSession)
                    echo RI::ni(2), "<br/><a class='add' href='", $Request->getDomainPath(), ltrim(CreatePath::getRequestURL($this->fullPath), '/'), "'>Add content</a>";
            }

        }

        echo RI::ni(1), "</div>";
        echo RI::ni(), "</div>";
    }

    /**
     * Write all support headers used by this renderer
     * @param IRequest $Request
     * @param IHeaderWriter $Head the writer inst to use
     * @return void
     */
    function writeHeaders(IRequest $Request, IHeaderWriter $Head) {
        $Head->writeStyleSheet(__DIR__ . '/assets/path-tip.css');
    }
}