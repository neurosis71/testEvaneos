<?php

class TemplateManager
{
    
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

        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $data);
        $replaced->content = $this->computeText($replaced->content, $data);

        return $replaced;
    }

    private function computeText($text, array $data)
    {
        $this->_appContext = ApplicationContext::getInstance();

        $this->_quote = (isset($data['quote']) and $data['quote'] instanceof Quote) ? $data['quote'] : null;

        if ($this->_quote)
        {
            $this->_quoteFromRepository = QuoteRepository::getInstance()->getById($this->_quote->id);
            $this->_quoteSite = SiteRepository::getInstance()->getById($this->_quote->siteId);
            $this->_quoteDestination = DestinationRepository::getInstance()->getById($this->_quote->destinationId);

            if(strpos($text, '[quote:destination_link]') !== false){
                $destination = DestinationRepository::getInstance()->getById($this->_quote->destinationId);
            }

            $containsSummaryHtml = strpos($text, '[quote:summary_html]');
            $containsSummary     = strpos($text, '[quote:summary]');

            if ($containsSummaryHtml !== false || $containsSummary !== false) {
                if ($containsSummaryHtml !== false) {
                    $text = str_replace(
                        '[quote:summary_html]',
                        Quote::renderHtml($this->_quoteFromRepository),
                        $text
                    );
                }
                if ($containsSummary !== false) {
                    $text = str_replace(
                        '[quote:summary]',
                        Quote::renderText($this->_quoteFromRepository),
                        $text
                    );
                }
            }

            (strpos($text, '[quote:destination_name]') !== false) and $text = str_replace('[quote:destination_name]',$this->_quoteDestination->countryName,$text);
        }

        if (isset($this->_quoteDestination))
            $text = str_replace('[quote:destination_link]', $this->_quoteSite->url . '/' . $this->_quoteDestination->countryName . '/quote/' . $this->_quoteFromRepository->id, $text);
        else
            $text = str_replace('[quote:destination_link]', '', $text);

        /*
         * USER
         * [user:*]
         */
        $this->_user  = (isset($data['user'])  and ($data['user']  instanceof User))  ? $data['user']  : $this->_appContext->getCurrentUser();
        if($this->_user) {
            (strpos($text, '[user:first_name]') !== false) and $text = str_replace('[user:first_name]'       , ucfirst(mb_strtolower($this->_user->firstname)), $text);
        }

        return $text;
    }
}
