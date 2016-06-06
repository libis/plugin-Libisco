<?php
require_once LIBCO_DIR."/models/LibcoService.php";
$formAttributes = array(
    'action' => url('libco/libco/search'),
    'method' => 'GET',
    'class'  => 'europeana-search',
    'class'  => 'llibco-search',
);
?>
<?php $view = get_view();?>
<?php echo $this->form('libco-search-form', $formAttributes); ?>

<div><?php echo flash(); ?></div>

<div id="libco-search-box">
    <div class="field">
        <?php echo $this->formText('q', $query, array('title' => __('Search keywords'), 'size' => 40, 'placeholder' => 'Search...')); ?>
        <?php echo $this->formButton('', __('Search'), array('type' => 'submit')); ?>
    </div> 

    <?php
    $libcoService = new LibcoService();
    $searchSources = $libcoService->getSearchSources();
    ?>
    <div class="field">
        <label><?php //echo __("Search By"); ?></label>
        <div class="inputs">
            <?php //echo $this->formSelect('searchfilter', 'Search By', array('class' => 'existing-element-drop-down'),array('All' => '*', 'title' => 'Title', 'id' => 'Id'), array()); ?>
        </div>
    </div>

    <div class="field">
        <label><?php echo __("Select Search Source"); ?></label>
        <div class="inputs">
            <ul>
            <?php
            if(!empty($searchSources) && is_array($searchSources)){
				$excludeSource = array("NLA");
                foreach($searchSources as $sourceName){
                    if(in_array($sourceName, $excludeSource))
                        continue;					
                    echo "<li>";
                    echo $view->formCheckbox('searchsource_'.$sourceName, null, array('checked'=>'checked'));
                    echo $sourceName;
                    echo "</li>";
                }
            }
            else{
                echo 'Error in retrieving search sources from Europeana Space.';
            }
            ?>
             </ul>
        </div>
    </div>    

        <p><a target="_blank" href="<?php echo url('espaceapisearch'); ?>"><?php echo __("Need help?"); ?></a></p>
    </form>
</div>
