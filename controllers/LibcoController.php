<?php
/**
 * User: NaeemM
 */

class Libco_LibcoController extends Omeka_Controller_AbstractActionController{

    /**
     * Searches Europeana Space Repository.
     */
    public function searchAction()
    {
        $searchQuery = $this->getParam('q');
        if(empty($searchQuery))
            return;

        $parameters = $this->getAllParams();
        $sources = array();
        foreach($parameters as $param => $value){
            if (strpos($param,'searchsource_') !== false && $value === "1") {
             $sources[] = str_replace('searchsource_','',$param);
            }
        }

        if(sizeof($sources)){
            $libcoService = new LibcoService();
            $result = $libcoService->search($searchQuery, $sources, $this->getCurrentPage());
            if(!empty($result)){
                // 'api/advancedsearch'
                if(empty($result['responces'])){
                    $this->_helper->flashMessenger('Search result not returned from the server.',  'error');
                    return;
                }
                $result = $result['responces'];
                $result = $libcoService->normalizeResult($result, $searchQuery);

                if ($result['totalResults']) {
                    Zend_Registry::set('pagination', array(
                        'page' => $this->getCurrentPage(),
                        'per_page' => 100,
                        'total_results' => $result['totalResults']
                    ));}
                $this->view->assign($result);
            }
        }
        else
            $this->_helper->flashMessenger('Search source not selected.',  'error');
    }

    private function getCurrentPage()
    {
        static $currentPage;

        if (!isset($currentPage)) {
            $currentPage = (int) $this->getParam('page');
            $currentPage = ($currentPage > 0) ? $currentPage : 1;
        }

        return $currentPage;
    }
    
}