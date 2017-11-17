<?php

class TemplateManager
{
    
    //markup association to the processing function
    private $_markups = array(
        '[quote:destination_link]' => 'getDestinationLink',
        '[quote:summary_html]' => 'quoteToHtml',
        '[quote:summary]' => 'quoteToText',
        '[quote:destination_name]' => 'getDestinationName',
        '[user:first_name]' => ''
    );
    
    private $_appContext = NULL;
    private $_quote = NULL;
    private $_user = NULL;
    private $_quoteFromRepository = NULL;
    private $_quoteSite = NULL;
    private $_quoteDestination = NULL;
    
    
    
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

    private function computeText($text)
    {
        
        foreach ($this->_markups as $markup => $processingFunction) {
            
            if(strpos($text, $markup)){
                $text = str_replace(
                        $markup, $this->$processingFunction(), $text
                );
            }
            
        }

        /*
         * USER
         * [user:*]
         */

        if($this->_user) {
            (strpos($text, '[user:first_name]') !== false) and $text = str_replace('[user:first_name]'       , ucfirst(mb_strtolower($this->_user->firstname)), $text);
        }

        return $text;
    }
    
    private function quoteToHtml(){
        return Quote::renderHtml($this->_quoteFromRepository);
    }
    
    private function quoteToText(){
        return Quote::renderText($this->_quoteFromRepository);
    }
    
    private function getDestinationName(){
        return $this->_quoteDestination->countryName;
    }
    
    private function getDestinationLink(){
        if ($this->_quoteDestination !== NULL) {
            return $this->_quoteSite->url . '/' . $this->_quoteDestination->countryName . '/quote/' . $this->_quoteFromRepository->id;
        } else {
            return '';
        }
    }
}
