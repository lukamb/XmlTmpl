<?php

namespace XmlTmpl;

use Nette;

/**
 * Pomocné úložiště pro vykreslování šablon
 * @author Lukáš Ambrož
 */
class TempStorage
{
    
    /**
     * Index pro uložení příznaku dědičnosti v poli reprezentujícím blok
     */
    const BLOCK_EXT = 0;
    
    /**
     * Index pro uložení názvu funkcí bloku v poli reprezentujícím blok
     */
    const BLOCK_FNAMES = 1;
    
    /**
     * Příznak, zda se jedná o vkládaný soubor
     * @var int
     */
    private $included = 0;
    
    /**
     * Příznak, zda se program nachází v bloku (pro vkládané soubory)
     * @var bool
     */
    private $block = false;
    
    /**
     * Zásobník iterátorů pro cykly
     * @var array
     */
    private $iterators = array();
    
    /**
     * Zásobník formulářových kontejnerů
     * @var array
     */
    private $forms = array();
    
    /**
     * Seznam bloků
     * @var array
     */
    private $blocks = array();
    
    /**
     * Seznam rozšiřujících šablon
     * @var array
     */
    private $descendants = array();
    
    /**
     * Úložiště definovaných proměnných
     * @var array
     */
    private static $vars = array();
    
    /**
     * Nastaví příznak vkládaného souboru
     */
    public function setIncluded()
    {
        $this->included++;
    }
    
    /**
     * Zruší nastavení příznaku vkládaného souboru
     */
    public function unsetIncluded()
    {
        $this->included--;
    }
    
    /**
     * Vrátí nastavení příznaku vkládaného souboru
     * @return bool Nastavení příznaku
     */
    public function isIncluded()
    {
        return $this->included > 0;
    }
    
    /**
     * Nastaví příznak bloku
     */
    public function setBlock()
    {
        $this->block = true;
    }
    
    /**
     * Vrátí nastavení příznaku bloku
     * @return bool Nastavení příznaku
     */
    public function isBlock()
    {
        return $this->block;
    }
    
    /**
     * Vloží zadaný iterátor do zásobníku a vrátí jej
     * @param Nette\Iterators\CachingIterator $iterator Iterátor
     * @return Nette\Iterators\CachingIterator Iterátor
     */
    public function pushIterator(Nette\Iterators\CachingIterator $iterator)
    {
        $this->iterators[] = $iterator;
        
        return $iterator;
    }
    
    /**
     * Vrátí a odstraní iterátor ze zásobníku
     * @return Nette\Iterators\CachingIterator Iterátor
     */
    public function popIterator()
    {
        return array_pop($this->iterators);
    }
    
    /**
     * Vloží kontejner formuláře do zásobníku a vrátí jej
     * @param Nette\Forms\Container $form Vkládaný kontejner
     * @return Nette\Forms\Container Vložený kontejner
     */
    public function pushForm(Nette\Forms\Container $form)
    {
        $this->forms[] = $form;
        
        return $form;
    }
    
    /**
     * Vrátí kontejner formuláře z vrcholu zásobníku
     * @return Nette\Forms\Container Kontejner formuláře
     */
    public function topForm()
    {
        return end($this->forms);
    }
    
    /**
     * Odstraní kontejner formuláře z vrcholu zásobníku a vrátí jej
     * @return Nette\Forms\Container Kontejner formuláře
     */
    public function popForm()
    {
        return array_pop($this->forms);
    }
    
    /**
     * Uloží blok do seznamu a automaticky mu přiřadí správnou funkci podle
     * toho, jestli je blok definován v rámci dědičnosti nebo má být přepsán
     * @param string $name Jméno bloku
     * @param string $fname Jméno funkce reprezentující blok
     * @param bool $ext Příznak, zda je blok definován v rámci dědičnosti
     */
    public function defineBlock($name, $fname, $ext = false)
    {
        if (!array_key_exists($name, $this->blocks)) {
            // Přidání bloku, pokud ještě nebyl definován
            $this->blocks[$name][self::BLOCK_EXT] = $ext;
            $this->blocks[$name][self::BLOCK_FNAMES][] = $fname;
        } elseif (!$this->blocks[$name][self::BLOCK_EXT]) {
            // Přidání nové definice bloku, pouze pokud se nejedná o dědičnost
            $this->blocks[$name][self::BLOCK_FNAMES][] = $fname;
        }
    }
    
    /**
     * Vrací jméno funkce, která má reprezentovat zadaný blok
     * @param string $name Jméno bloku
     * @return string Jméno funkce, NULL pokud blok se zadaným názvem neexistuje
     */
    public function getBlockFunction($name)
    {
        if (!array_key_exists($name, $this->blocks))
            return NULL;
        
        // V případě dědičnosti obsahuje pole funkcí pouze jednu funkci
        return end($this->blocks[$name][self::BLOCK_FNAMES]);
    }
    
    /**
     * Ověří, zda zadaná šablona potomka již neexistuje v hierarchii dědičnosti
     * (kvůli zamezení zacyklení)
     * @param string $id ID šablony
     * @return bool Výsledek ověření
     */
    public function checkDescendantTmpl($id)
    {
        if (in_array($id, $this->descendants))
            return false;
        
        $this->descendants[] = $id;
        return true;
    }
    
    /**
     * Uloží zadané pole obsahující všechny proměnné definované v šabloně
     * (vynechá objekt šablony) pro jejich přenos mezi šablonami (například
     * při vkládání souborů)
     * @param array $vars Pole s definovanými proměnnými
     */
    public function setDefinedVars(array $vars)
    {
        self::$vars = $vars;
        
        if (array_key_exists('template', self::$vars))
                unset(self::$vars['template']);
    }
    
    /**
     * Vrátí pole obsahující definované proměnné
     * @return array Pole s definovanými proměnnými
     */
    public function getDefinedVars()
    {
        return self::$vars;
    }
    
}

?>
