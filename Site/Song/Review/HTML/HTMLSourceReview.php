<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 2/24/2015
 * Time: 2:51 PM
 */
namespace Site\Song\Review\HTML;

use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\Header\IHeaderWriter;
use CPath\Render\HTML\Header\IHTMLSupportHeaders;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\IRequest;
use Site\Account\DB\AccountEntry;
use Site\Song\Review\DB\ReviewEntry;

class HTMLSourceReview implements IRenderHTML, IHTMLSupportHeaders
{
    /**
     * @var ReviewEntry
     */
    private $SourceReview;
    /**
     * @var AccountEntry
     */
    private $Reviewer;

    public function __construct(ReviewEntry $SourceReview = null, AccountEntry $Reviewer=null) {
        $this->SourceReview = $SourceReview;
        $this->Reviewer = $Reviewer;
    }

    /**
     * Render request as html
     * @param IRequest $Request the IRequest inst for this render which contains the request and remaining args
     * @param IAttributes $Attr
     * @param IRenderHTML $Parent
     * @return String|void always returns void
     */
    function renderHTML(IRequest $Request, IAttributes $Attr = null, IRenderHTML $Parent = null) {
        echo RI::ni(), "<div class='review'>";

        echo RI::ni(1), "<span class='review-title'>";
        if($this->SourceReview)
            echo $this->SourceReview->getReviewTitle();
        echo RI::ni(1), "</span>";

        echo RI::ni(1), "<span class='review-account'>Review by<br/>";
        if($this->Reviewer)
            $this->Reviewer->renderHTML($Request);
        echo RI::ni(1), "</span>";

        echo RI::ni(1), "<div class='review-content'>";
        if($this->SourceReview)
            echo $this->SourceReview->getFormattedReview();
        echo RI::ni(1), "</div>";

        echo RI::ni(0), "</div>";

    }

    /**
     * Write all support headers used by this renderer
     * @param IRequest $Request
     * @param IHeaderWriter $Head the writer inst to use
     * @return void
     */
    function writeHeaders(IRequest $Request, IHeaderWriter $Head) {
//        $Head->writeScript(__DIR__ . '/assets/song-review.js');
        $Head->writeStyleSheet(__DIR__ . '/assets/song-review.css');
    }
}