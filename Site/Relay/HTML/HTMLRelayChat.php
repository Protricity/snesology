<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/3/2015
 * Time: 5:25 PM
 */
namespace Site\Relay\HTML;

use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\Header\IHeaderWriter;
use CPath\Render\HTML\Header\IHTMLSupportHeaders;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\IRequest;
use Site\Relay\PathLog;

class HTMLRelayChat implements IRenderHTML, IHTMLSupportHeaders
{

    /** @var ExecutableRenderer */
    private $Render;

    public function __construct(IRequest $Request, $path) {
        $PathLog = new PathLog($path);
        $this->Render = new ExecutableRenderer($PathLog, false);
        $this->Render->execute($Request);
    }

    /**
     * Render request as html
     * @param IRequest $Request the IRequest inst for this render which contains the request and remaining args
     * @param IAttributes $Attr
     * @param IRenderHTML $Parent
     * @return String|void always returns void
     */
    function renderHTML(IRequest $Request, IAttributes $Attr = null, IRenderHTML $Parent = null) {
        $Render = $this->Render;
        $Render->renderHTML($Request, $Attr, $Parent);
    }

    /**
     * Write all support headers used by this renderer
     * @param IRequest $Request
     * @param IHeaderWriter $Head the writer inst to use
     * @return void
     */
    function writeHeaders(IRequest $Request, IHeaderWriter $Head) {
        $Render = $this->Render;
        $Render->writeHeaders($Request, $Head);
    }
}