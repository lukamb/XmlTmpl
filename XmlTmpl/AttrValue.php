<?php

namespace XmlTmpl;

/**
 * Podpora pro zpracování hodnot XML atributů obsahujících výrazy
 * @author Lukáš Ambrož
 */
class AttrValue
{
    
    /**
     * Příznak, zda zadaná hodnota obsahuje výrazy
     * @var bool
     */
    private $expression = false;
    
    /**
     * Uložená hodnota výrazu
     * @var string
     */
    private $value = NULL;
    
    /**
     * Nastaví hodnotu atributu, a pokud obsahuje výrazy, převede je na PHP výraz
     * @param string $value Hodnota atributu
     * @return bool Úspěch operace (vrátí false, pokud je hodnota zadána nesprávně)
     */
    public function setValue($value)
    {
        if (!Compiler::validateText($value))
            return false;
        
        if ($this->containsExpressions($value)) {
            $this->expression = true;
            $this->value = $this->createPhpExpression($value);
        } else
            $this->value = $value;
        
        $this->value = Compiler::unescapeText($this->value);
        
        return true;
    }
    
    /**
     * Vrátí nastavenou hodnotu jako řetězec obsahující PHP výraz
     * @return string PHP výraz
     */
    public function getPhpExpression()
    {
        return $this->expression ? $this->value : '\''.$this->value.'\'';
    }
    
    /**
     * Vrátí nastavenou hodnotu jako řetězec nebo NULL pokud původní hodnota
     * obsahovala výrazy
     * @return string Hodnota atributu jako řetězec
     */
    public function getString()
    {
        return $this->expression ? NULL : $this->value;
    }
    
    /**
     * Ověří, zda zadaný text obsahuje výrazy
     * @param string $text Vstupní text (validní)
     * @return bool Výsledek operace
     */
    private function containsExpressions($text)
    {
        return preg_match('/((^)|([^{]))\{[^{]/', $text) ? true : false;
    }
    
    /**
     * Ze zadaného textu obsahujícího výrazy vytvoří PHP výraz
     * @param string $text Vstupní text (validní)
     * @return string PHP výraz
     */
    private function createPhpExpression($text)
    {
        // Zpracování okrajů řetězce
        $text = preg_match('/^\{[^{]/', $text) ? ltrim($text, '{') : '\''.$text;
        $text = preg_match('/[^}]\}$/', $text) ? rtrim($text, '}') : $text.'\'';

        // Konkatenace výrazů za sebou
        $text = preg_replace('/([^}])\}\{([^{])/', '$1.$2', $text);

        // Konkatenace výrazů s textem
        $text = preg_replace('/([^{])\{([^{])/', '$1\'.$2', $text);
        $text = preg_replace('/([^}])\}([^}])/', '$1.\'$2', $text);

        return $text;
    }
    
}

?>
