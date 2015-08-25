<?php

/**
 * Pomocné funkce pro výrazy v XML šablonách (usnadňují použití některých funkcí z Nette)
 * @author Lukáš Ambrož
 */
class f
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
     * Nastaví referenci na objekt zpracovávané šablony
     * @param Nette\Templating\Template $template Objekt šablony
     */
    public static function setTemplate(Nette\Templating\Template $template)
    {
        self::$template = $template;
    }
    
    /**
     * Vrátí odkaz na základě Nette zápisu odkazů
     * @param string $destination Zadaný odkaz
     * @param array $args Argumenty
     * @return string Odkaz
     * @throws Nette\InvalidStateException Pokud odkaz nelze vygenerovat (není k dispozici presenter)
     */
    public static function link($destination, array $args = array())
    {
        // Ověření, zda je nastaven objekt šablony
        if (self::$template === NULL)
            throw new Nette\InvalidStateException('Template object must be set before accessing its member.');
        
        // Ověření, zda zadaný objekt šablony obsahuje referenci na presenter
        if (!isset(self::$template->_presenter))
            throw new Nette\InvalidStateException('Template object is not connected with any presenter.');
        if (!(self::$template->_presenter instanceof Nette\Application\UI\PresenterComponent))
            throw new Nette\InvalidStateException('Template object is not connected with any presenter.');
        
        // Zavolání příslušné metody presenteru
        return self::$template->_presenter->link($destination, $args);
    }
    
    /**
     * Ověří, zda zadaný odkaz (ve tvaru Nette zápisu) směřuje na aktuální stránku
     * @param string $destination Zadaný odkaz
     * @param array $args Argumenty
     * @return bool Výsledek operace
     * @throws Nette\InvalidStateException Pokud odkaz nelze ověřit (není k dispozici presenter)
     */
    public static function isLinkCurrent($destination = NULL, array $args = array())
    {
        // Ověření, zda je nastaven objekt šablony
        if (self::$template === NULL)
            throw new Nette\InvalidStateException('Template object must be set before accessing its member.');
        
        // Ověření, zda zadaný objekt šablony obsahuje referenci na presenter
        if (!isset(self::$template->_presenter))
            throw new Nette\InvalidStateException('Template object is not connected with any presenter.');
        if (!(self::$template->_presenter instanceof Nette\Application\UI\PresenterComponent))
            throw new Nette\InvalidStateException('Template object is not connected with any presenter.');
        
        // Zavolání příslušné metody presenteru
        return self::$template->_presenter->isLinkCurrent($destination, $args);
    }
    
    /**
     * Ověří, zda řetězec začíná zadaným podřetězcem
     * @param string $haystack Testovaný řetězec
     * @param string $needle Hledaný podřetězec
     * @return bool Úspěch operace
     */
    public static function startsWith($haystack, $needle)
    {
        return Nette\Utils\Strings::startsWith($haystack, $needle);
    }
    
    /**
     * Ověří, zda řetězec končí zadaným podřetězcem
     * @param string $haystack Testovaný řetězec
     * @param string $needle Hledaný podřetězec
     * @return bool Úspěch operace
     */
    public static function endsWith($haystack, $needle)
    {
        return Nette\Utils\Strings::endsWith($haystack, $needle);
    }
    
    /**
     * Ověří, zda řetězec obsahuje zadaný podřetězec
     * @param string $haystack Testovaný řetězec
     * @param string $needle Hledaný podřetězec
     * @return bool Úspěch operace
     */
    public static function contains($haystack, $needle)
    {
        return Nette\Utils\Strings::contains($haystack, $needle);
    }
    
    /**
     * Porovnání dvou řetězců nebo jejich částí bez ohledu na velikost písmen
     * @param string $left První řetězec
     * @param string $right Druhý řetězec
     * @param int $len Počet znaků pro porovnání (je-li 0 - celé řetězce, < 0 - konce řetězců)
     * @return bool Úspěch operace
     */
    public static function compare($left, $right, $len = NULL)
    {
        return Nette\Utils\Strings::compare($left, $right, $len);
    }
    
    /**
     * Vygeneruje náhodný řetězec, lze specifikovat jeho délku nebo požadovanou sadu znaků
     * @param int $length Délka řetězce
     * @param string $charlist Specifikace znaků (lze i intervaly, např. a-z)
     * @return string Vygenerovaný řetězec
     */
    public static function random($length = 10, $charlist = '0-9a-z')
    {
        return Nette\Utils\Strings::random($length, $charlist);
    }
    
}

?>
