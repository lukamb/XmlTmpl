<?php

namespace XmlTmpl;

use Nette;

/**
 * Umožňuje definici a zpracování uživatelských značek
 * @author Lukáš Ambrož
 */
class UserTags
{
    
    /**
     * Reference na objekt Compiler
     * @var Compiler
     */
    private $compiler;
    
    /**
     * Pole definovaných značek
     * @var array
     */
    private $tags;
    
    /**
     * Inicializace objektu
     */
    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
        $this->tags = array();
    }
    
    /**
     * Zaregistruje novou uživatelskou značku
     * @param string $name Jméno značky
     * @param callable $empty Funkce pro zpracování prázdné značky nebo NULL
     * @param callable $start Funkce pro zpracování otevírací značky nebo NULL
     * @param callable $end Funkce pro zpracování koncové značky nebo NULL
     * @return bool Úspěch operace
     */
    public function registerUserTag($name, $empty, $start, $end)
    {
        // Ověření zadaných funkcí pro zpracování jednotlivých typů značek
        if ($empty !== NULL && !is_callable($empty))
            return false;
        if ($start !== NULL && !is_callable($start))
            return false;
        if ($end !== NULL && !is_callable($end))
            return false;
        
        $this->tags[$name] = array($empty, $start, $end);
        
        return true;
    }
    
    /**
     * Zpracování uživatelské značky
     * @param string $name Jméno značky
     * @param int $type Typ značky
     * @param array $attrs Atributy
     * @throws Nette\Templating\FilterException Pokud značka neexistuje nebo není správně použita
     */
    public function processTag($name, $type, $attrs)
    {
        // Ověření, zda značka existuje
        if (!array_key_exists($name, $this->tags))
            throw new Nette\Templating\FilterException("Unknown tag: $name");
        
        // Ověření správného použití značky
        if (is_null($this->tags[$name][$type]))
            throw new Nette\Templating\FilterException("Inappropriate use of tag: $name");
        
        // Volání zaregistrované funkce pro zpracování značky
        $res = call_user_func($this->tags[$name][$type], $attrs);
        if ($res === false)
            throw new Nette\Templating\FilterException("Error while processing tag: $name");
        
        // Připojení vráceného PHP kódu k výstupu
        $this->compiler->appendOutput($res);
    }
    
}

?>
