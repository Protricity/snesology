<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/1/2015
 * Time: 8:07 PM
 */
namespace Site\Render\PopUpBox;

use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\Header\IHeaderWriter;
use CPath\Render\HTML\Header\IHTMLSupportHeaders;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\IRequest;

class HTMLPopUpBox implements IRenderHTML, IHTMLSupportHeaders
{
    const CLASS_INFO = 'info';
    const CLASS_DESCRIPTION = 'description';
    const CLASS_IMPORTANT = 'important';

    private $content;
    private $class;
    private $caption;

    public function __construct($content, $class=self::CLASS_INFO, $caption=null) {
        $this->content = $content;
        $this->class = $class;
        $this->caption = $caption;
    }

    function renderContent(IRequest $Request, IRenderHTML $Parent = null) {
        echo RI::ni(), $this->content;
    }

    /**
     * Render request as html
     * @param IRequest $Request the IRequest inst for this render which contains the request and remaining args
     * @param IAttributes $Attr
     * @param IRenderHTML $Parent
     * @return String|void always returns void
     */
    function renderHTML(IRequest $Request, IAttributes $Attr = null, IRenderHTML $Parent = null) {
        $class = $this->class ? ' ' . $this->class : '';
        echo RI::ni(), "<div class='popup{$class}'>";
        if ($this->caption)
            echo RI::ni(1), "<span>", $this->caption, "</span>";
        if($this->content) {
            echo RI::ni(1), "<div class='content'>";

            if ($this->class)
                echo RI::ni(2), "<div class='popup{$class}'></div>";

            $this->renderContent($Request, $Parent);

            echo RI::ni(1), "</div>";
        }
        echo RI::ni(), "</div>";
    }

    /**
     * Write all support headers used by this renderer
     * @param IRequest $Request
     * @param IHeaderWriter $Head the writer inst to use
     * @return void
     */
    function writeHeaders(IRequest $Request, IHeaderWriter $Head) {
        $Head->writeStyleSheet(__DIR__ . '/assets/popup.css');
    }
}