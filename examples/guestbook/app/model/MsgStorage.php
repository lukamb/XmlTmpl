<?php

/**
 * Poskytuje potřebné operace pro načítání a ukládání zpráv do XML souboru
 * @author Lukáš Ambrož
 */
class MsgStorage
{
    
    /**
     * Název XML souboru pro uložení zpráv
     * @var string
     */
    private $file;
    
    /**
     * Inicializace objektu
     * @param string $file Název XML souboru pro uložení zpráv
     */
    public function __construct($file)
    {
        $this->file = $file;
    }
    
    /**
     * Načte zprávy z XML souboru do asociativního pole
     * @return array Pole obsahující uložené zprávy
     */
    public function readMessages()
    {
        $res = array();
        
        if (!file_exists($this->file))
            return $res;
        
        $xml = simplexml_load_file($this->file);
        
        foreach ($xml->message as $msg) {
            $i = count($res);
            
            $res[$i]['from'] = $msg->from;
            $res[$i]['email'] = $msg->email;
            $res[$i]['subject'] = $msg->subject;
            $res[$i]['text'] = $msg->text;
        }
        
        return array_reverse($res);
    }
    
    /**
     * Uloží zadanou zprávu do XML souboru
     * @param string $from Odesílatel zprávy
     * @param string $email E-mail odesílatele
     * @param string $subject Předmět zprávy
     * @param string $text Text zprávy
     * @return bool Úspěch operace
     */
    public function saveMessage($from, $email, $subject, $text)
    {
        if (file_exists($this->file))
            $xml = simplexml_load_file($this->file);
        else
            $xml = new SimpleXMLElement('<messages></messages>');
        
        $msg = $xml->addChild('message');
        $msg->addChild('from', Nette\Templating\Helpers::escapeXML($from));
        $msg->addChild('email', Nette\Templating\Helpers::escapeXML($email));
        $msg->addChild('subject', Nette\Templating\Helpers::escapeXML($subject));
        $msg->addChild('text', Nette\Templating\Helpers::escapeXML($text));
        
        return $xml->asXML($this->file);
    }
    
}

?>
