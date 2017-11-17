<?php

class TemplateManager
{
    
    //markup association to the processing functions
    private $_markups = array(
        '[quote:destination_link]' => 'getDestinationLink',
        '[quote:summary_html]' => 'quoteToHtml',
        '[quote:summary]' => 'quoteToText',
        '[quote:destination_name]' => 'getDestinationName',
        '[user:first_name]' => 'getUserFirstName'
    );
    
    private $_appContext = NULL;
    private $_quote = NULL;
    private $_user = NULL;
    private $_quoteFromRepository = NULL;
    private $_quoteSite = NULL;
    private $_quoteDestination = NULL;
    
    
    /**
     * 
     * Public function called to replace the markups in the passed template by the passed data
     * 
     * @param Template $tpl
     * @param array $data
     * @return String
     * @throws \RuntimeException
     */
    public function getTemplateComputed(Template $tpl, array $data)
    {
        if ($tpl === null) {
            throw new \RuntimeException('no tpl given');
        }

        $this->initializeData($data);
        
        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject);
        $replaced->content = $this->computeText($replaced->content);

        return $replaced;
    }
    
    /**
     * 
     * Initialize the objects needed to process the template
     * 
     * @param array $data
     * @throws \RuntimeException
     */
    private function initializeData(array $data) {

        $this->_appContext = ApplicationContext::getInstance();
        $this->_quote = (isset($data['quote']) and $data['quote'] instanceof Quote) ? $data['quote'] : NULL;
        $this->_user = (isset($data['user']) and ( $data['user'] instanceof User)) ? $data['user'] : $this->_appContext->getCurrentUser();

        if ($this->_quote !== NULL) {
            $this->_quoteFromRepository = QuoteRepository::getInstance()->getById($this->_quote->id);
            $this->_quoteSite = SiteRepository::getInstance()->getById($this->_quote->siteId);
            $this->_quoteDestination = DestinationRepository::getInstance()->getById($this->_quote->destinationId);
        } else {
            //prefere lancer une exception ne connaissant pas les spec
            //facile a modifier si nous voulons pouvoir traiter des messages affchant seulement des donees user
            throw new \RuntimeException('no quote given');
        }
    }

    /**
     * 
     * Call the processing functions assigned to each markup
     * 
     * @param type $text
     * @return String
     */
    private function computeText($text)
    {
        
        foreach ($this->_markups as $markup => $processingFunction) {
            
            if(strpos($text, $markup)){
                $text = str_replace(
                        $markup, $this->$processingFunction(), $text
                );
            }
            
        }

        return $text;
    }
    
    /**
     * Render the quote to HTML
     * 
     * @return String
     */
    private function quoteToHtml(){
        return Quote::renderHtml($this->_quoteFromRepository);
    }
    
     /**
     * Render the quote to text
     * 
     * @return String
     */
    private function quoteToText(){
        return Quote::renderText($this->_quoteFromRepository);
    }
    
    /**
     * 
     * Return the quote destination name
     * 
     * @return String
     */
    private function getDestinationName(){
        return $this->_quoteDestination->countryName;
    }
    
    /**
     * 
     * Return the quote destination link
     * 
     * @return String
     */
    private function getDestinationLink(){
        if ($this->_quoteDestination !== NULL) {
            return $this->_quoteSite->url . '/' . $this->_quoteDestination->countryName . '/quote/' . $this->_quoteFromRepository->id;
        } else {
            return '';
        }
    }
    
    /**
     * 
     * Return the formatted user first name
     * 
     * @return String
     */
    private function getUserFirstName(){
        if ($this->_user !== NULL) {
            return ucfirst(mb_strtolower($this->_user->firstname));
        } else {
            return '';
        }
    }
}
