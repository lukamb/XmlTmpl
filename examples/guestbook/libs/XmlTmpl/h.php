<?php

/**
 * Zajišťuje volání helperů ve výrazech XML šablon
 * @author Lukáš Ambrož
 */
class h
{
    
    /**
     * Reference na objekt zpracovávané šablony
     * @var Nette\Templating\Template
     */
    private static $template = NULL;
    
    /**
     * Statická třída - nelze instancovat
     * @throws Nette\StaticClassException Nelze instancovat
     */
    final public function __construct()
    {
        throw new Nette\StaticClassException();
    }
    
    /**
     * Zajišťuje volání helperů registrovaných v zadané šabloně
     * @param string $name Jméno helperu
     * @param array $arguments Argumenty
     * @return string Řetězec po aplikaci helperu
     * @throws Nette\InvalidStateException Není-li nastaven objekt šablony
     */
    public static function __callStatic($name, $arguments)
    {
        if (self::$template === NULL)
            throw new Nette\InvalidStateException('Template object must be set before calling its helpers.');
        
        return call_user_func_array(array(self::$template, $name), $arguments);
    }
    
    /**
     * Nastaví referenci na objekt zpracovávané šablony
     * @param Nette\Templating\Template $template Objekt šablony
     */
    public static function setTemplate(Nette\Templating\Template $template)
    {
        self::$template = $template;
    }
    
}

?>
