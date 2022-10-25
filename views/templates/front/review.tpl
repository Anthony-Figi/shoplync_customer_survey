<!-- BulkOrder - Block customer account -->
{extends file='page.tpl'}
{block name='page_content'}

<!-- MODULE BulkOrder -->
<div class="card-block block-border">
    <div class="row">
        <div class="col-md-12 text-center">
            <h1 class="card-title text-center">
                <i class="em-4 material-icons rtl-no-flip done">{$top_icon nofilter}</i>
            </h1>
            <h1>{$title_str}</h1>
            <h4 class="py-1">{$subtitle_str nofilter}</h4>
            {if !isset($g_link) && !isset($fb_link) && !isset($review_box)}
            <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}" class="btn btn-primary">Contact Us</a>
            {/if}
            <hr class="mx-3"/>
            <div id="submit-inputs" class="mx-auto py-1" style="width: 40%;">
                {if isset($g_link)}
                <a href="{$g_link}" class="d-block btn btn-primary mb-1" target="_blank">
                    <i class="em-2-5 fa fa-google-plus-circle" style="margin-right: 5px;vertical-align: middle;"></i>
                    <span>Review Us On Google</span>
                </a>
                {/if}
                {if isset($fb_link)}
                <a href="{$fb_link}" class="d-block btn btn-primary mb-1" target="_blank">
                    <i class="em-2-5 fa fa-facebook-square" style="margin-right: 5px;vertical-align: middle;"></i>
                    <span>Recommend Us On Facebook</span>
                </a>
                {/if}
                {if isset($review_box)}
                <textarea class="form-control" id="feedbackBox" name="feedbackBox" title="Feedback Textbox" rows="10" cols="50" required=""></textarea>
                <button class="btn btn-primary form-control-submit d-sm-inline" onclick="SubmitFeedback();" type="button">
                    Submit Feedback
                </button>
                {/if}
            </div>
            <div id="feedback-received" style="display:none;">
                <h2 class="my-3">
                    <i class="em-2 material-icons rtl-no-flip done" style="vertical-align: middle;">&#xE163;</i>
                    Comments Sent!
                </h2>
                <a href="{$link->getPageLink('index', true)|escape:'html':'UTF-8'}" class="btn btn-primary">
                    Browse Store
                </a>
            </div>
        </div>
        
    </div>
</div>
{/block}
<!-- END : MODULE BulkOrder -->
