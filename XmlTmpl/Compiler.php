<?php

namespace XmlTmpl;

use XMLReader;
use Nette;

require_once 'f.php';
require_once 'h.php';
require_once 'TempStorage.php';
require_once 'AttrValue.php';
require_once 'CoreTags.php';
require_once 'UserTags.php';

/**
 * Zajišťuje zpracování XML šablon
 * @author Lukáš Ambrož
 */
class Compiler
{
    
    /**
     * Název XML jmenného prostoru pro základní značky
     */
    const CORE_TAGS_NS = 'xmltmpl-core-tags';
    
    /**
     * Název XML jmenného prostoru pro uživatelské značky
     */
    const USER_TAGS_NS = 'xmltmpl-user-tags';
    
    /**
     * Název jmenného prostoru pro XHTML
     */
    const XHTML_NS = 'http://www.w3.org/1999/xhtml';
    
    
    
    /**
     * Typ značky - prázdná značka
     */
    const TAG_EMPTY = 0;
    
    /**
     * Typ značky - počáteční značka
     */
    const TAG_START = 1;
    
    /**
     * Typ značky - koncová značka
     */
    const TAG_END = 2;
    
    
    
    /**
     * Objekt pro zpracování základních značek
     * @var CoreTags
     */
    private $coreTags;
    
    /**
     * Objekt pro zpracování uživatelských značek
     * @var UserTags
     */
    private $userTags;
    
    
    
    /**
     * Začátek generované PHP šablony
     * @var string
     */
    public $start = '';
    
    /**
     * Část generované šablony obsahující definici bloků
     * @var string
     */
    public $blocks = '';
    
    /**
     * Hlavní část generované šablony
     * @var string
     */
    public $body = '';
    
    
    
    /**
     * Příznak značící sekci remove
     * @var int
     */
    public $remove = 0;
    
    /**
     * Příznak značící sekci extends
     * @var bool
     */
    public $extends = false;
    
    /**
     * Příznak značící sekci bloku
     * @var bool
     */
    public $block = false;
    
    
    
    /**
     * Typ předchozího XML uzlu
     * @var int
     */
    public $lastType = NULL;
    
    /**
     * Jmenný prostor předchozí značky
     * @var string
     */
    public $lastNs = NULL;
    
    /**
     * Jméno předchozí značky
     * @var string
     */
    public $lastName = NULL;
    
    /**
     * Typ předchozí značky
     * @var int
     */
    public $lastTagType = NULL;
    
    
    
    /**
     * Inicializace objektu
     */
    public function __construct()
    {
        $this->coreTags = new CoreTags($this);
        $this->userTags = new UserTags($this);
    }
    
    /**
     * Při volání objektu jako metody
     * @param string $input Vstupní XML šablona
     * @return string Výstupní PHP šablona
     */
    public function __invoke($input)
    {
        return $this->compile($input);
    }
    
    /**
     * Řídí převod vstupní XML šablony na výstupní PHP kód
     * @param string $input Vstupní XML šablona
     * @return string Výstupní PHP šablona
     */
    public function compile($input)
    {
        // Inicializace vnitřních proměnných
        $this->start = '';
        $this->blocks = '';
        $this->body = '';
        
        $this->remove = 0;
        $this->extends = false;
        $this->block = false;
        
        $this->lastType = NULL;
        $this->lastNs = NULL;
        $this->lastName = NULL;
        $this->lastTagType = NULL;
        
        // Zpracování XML pomocí XMLReader
        $reader = new XMLReader();
        $reader->XML($input, 'UTF-8');
        
        // Vygenerování počátečního PHP kódu šablony
        $this->coreTags->processStart();
        
        // Čtení vstupního dokumentu
        while ($reader->read()) {
            switch ($reader->nodeType) {
                // Zpracování elementu
                case XMLReader::ELEMENT:
                case XMLReader::END_ELEMENT:
                    $this->processElement($reader);
                    break;
                
                // Zpracování textu elementů
                case XMLReader::TEXT:
                    $this->processText($reader);
                    break;
                
                // Zpracování komentářů
                case XMLReader::COMMENT:
                    $this->processComment($reader);
                    break;
                    
                // Zpracování ostatních uzlů
                default:
                    $this->processDefault($reader);
            }
        }
        
        // Vygenerování PHP kódu na konci šablony
        $this->coreTags->processEnd();
        
        return $this->start.$this->blocks.$this->body;
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
        return $this->userTags->registerUserTag($name, $empty, $start, $end);
    }
    
    /**
     * Připojí výstupní PHP kód k aktuálně generované části
     * @param string $output Výstupní kód
     */
    public function appendOutput($output)
    {
        if ($this->block) {
            $this->blocks .=$output;
            return;
        }
        
        $this->body .= $output;
    }
    
    /**
     * Test, zda je povolen výstup
     * @return bool Test
     */
    public function isOutputAllowed()
    {
        if ($this->remove)
            return false;
        
        if ($this->extends && !$this->block)
            return false;
        
        return true;
    }
    
    /**
     * Zpracuje element
     * @param XMLReader $cursor Aktuální pozice v XML dokumentu
     * @throws Nette\Templating\FilterException Při chybě v XML šabloně
     */
    private function processElement(XMLReader $cursor)
    {
        // Uložení typu značky
        if ($cursor->isEmptyElement)
            $type = Compiler::TAG_EMPTY;
        elseif ($cursor->nodeType == XMLReader::ELEMENT)
            $type = Compiler::TAG_START;
        else
            $type = Compiler::TAG_END;
        
        if ($cursor->namespaceURI === Compiler::CORE_TAGS_NS || $cursor->namespaceURI === Compiler::USER_TAGS_NS) {
            
            // Zpracování značky z xmltmpl-core-tags nebo xmltmpl-user-tags
            
            // Vytvoření pole atributů, hodnoty jsou reprezentovány objekty třídy AttrValue
            $attrs = array();
            while ($cursor->moveToNextAttribute()) {
                $attrs[$cursor->name] = new AttrValue();
                $attrs[$cursor->name]->setValue($cursor->value);
            }
            $cursor->moveToElement();
            
            // Zpracování základních nebo uživatelských značek
            if ($cursor->namespaceURI === Compiler::CORE_TAGS_NS)
                $this->coreTags->processTag($cursor->localName, $type, $attrs);
            elseif ($this->isOutputAllowed())
                $this->userTags->processTag($cursor->localName, $type, $attrs);
            
        } elseif ($this->isOutputAllowed()) {
            
            // Zpracování XHTML značky
            
            // Výpis XML deklarace a typu dokumentu před kořenovým elementem
            if ($cursor->name === 'html' && $type == Compiler::TAG_START) {
                $this->appendOutput("<?php echo '<?'; ?>xml version=\"1.0\" encoding=\"UTF-8\"<?php echo '?>'; ?>\n");
                $this->appendOutput("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n");
            }

            $this->appendOutput($type == Compiler::TAG_END ? '</' : '<');
            $this->appendOutput($cursor->name);

            // Výpis atributů
            if ($type != Compiler::TAG_END) {
                while ($cursor->moveToNextAttribute()) {
                    if ($cursor->prefix === 'xmlns' && $cursor->value === Compiler::CORE_TAGS_NS)
                        continue;
                    if ($cursor->prefix === 'xmlns' && $cursor->value === Compiler::USER_TAGS_NS)
                        continue;

                    if ($cursor->namespaceURI !== Compiler::CORE_TAGS_NS) {
                        $res = $cursor->value;
                        if (!Compiler::validateText($res))
                            throw new Nette\Templating\FilterException('Invalid expression syntax.');

                        $res = $this->processExpressions($res);
                        $res = Compiler::unescapeText($res);

                        $this->appendOutput(' '.$cursor->name.'="'.$res.'"');
                    }
                }
                $cursor->moveToElement();
            }

            $this->appendOutput($type == Compiler::TAG_EMPTY ? '/>' : '>');
            
        }
        
        // Uložení informací o naposledy zpracovaném elementu
        $this->lastType = $cursor->nodeType;
        $this->lastNs = $cursor->namespaceURI;
        $this->lastName = $cursor->localName;
        $this->lastTagType = $type;
    }
    
    /**
     * Zpracuje textové uzly
     * @param XMLReader $cursor Pozice v XML dokumentu
     * @throws Nette\Templating\FilterException Při chybě v XML šabloně
     */
    private function processText(XMLReader $cursor)
    {
        $this->lastType = $cursor->nodeType;
        
        if (!$this->isOutputAllowed())
            return;
        
        $res = $cursor->value;
        if (!Compiler::validateText($res))
            throw new Nette\Templating\FilterException('Invalid expression syntax.');
        $res = $this->processExpressions($res);
        $res = Compiler::unescapeText($res);
        
        $this->appendOutput($res);
    }
    
    /**
     * Zpracuje komentáře
     * @param XMLReader $cursor Pozice v XML dokumetu
     */
    private function processComment(XMLReader $cursor)
    {
        $this->lastType = $cursor->nodeType;
        
        if (!$this->isOutputAllowed())
            return;
        
        // Vynechá komentáře začínající <!---
        if (!preg_match('/^\-/', $cursor->value))
            $this->appendOutput($cursor->readOuterXml());
    }
    
    /**
     * Zpracování ostatních typů XML uzlů
     * @param type $cursor Pozice v XML dokumetu
     */
    private function processDefault($cursor)
    {
        if (!$this->isOutputAllowed())
            return;
        
        $this->appendOutput($cursor->readOuterXml());
    }
    
    /**
     * Výrazy v textu převede na jejich výpis v PHP
     * @param string $text Vstupní text (validní)
     * @return string Upravený text
     */
    private function processExpressions($text)
    {
        // Nahrazení zvlášť otevíracích a uzavíracích závorek
        $text = preg_replace('/((^)|([^{]))\{([^{])/', '$1<?php echo($4', $text);
        $text = preg_replace('/([^}])\}(([^}])|($))/', '$1); ?>$2', $text);

        return $text;
    }
    
    /**
     * Ověří správnost zápisu výrazů v zadaném textu
     * @param string $text Text pro ověření
     * @return bool Výsledek ověření
     */
    public static function validateText($text)
    {
        // Správné zdvojování závorek
        if (preg_match('/((^)|([^{]))\{\{\{(\{\{)*(([^{])|($))/', $text))
            return false;
        if (preg_match('/((^)|([^}]))\}\}\}(\}\})*(([^}])|($))/', $text))
            return false;

        // Odstranění zdvojených závorek pro usnadnění další kontroly
        $text = preg_replace('/\{\{(\{\{)*/', '', $text);
        $text = preg_replace('/\}\}(\}\})*/', '', $text);

        // Správná posloupnost závorek v textu
        if (substr_count($text, '{') != substr_count($text, '}'))
            return false;
        $sections = explode('{', $text);
        $first = array_shift($sections);
        if ($first && substr_count($first, '}') > 0)
            return false;
        foreach ($sections as $section) {
            if (substr_count($section, '}') != 1)
                return false;
        }

        // Neprázdný obsah výrazu
        if (preg_match('/\{\s*\}/', $text))
            return false;

        return true;
    }
    
    /**
     * Odstraní zdvojení složených závorek v textu
     * @param string $text Vstupní text (validní)
     * @return string Upravený text
     */
    public static function unescapeText($text)
    {
        // Převod zdvojených závorek na jednoduché
        $text = preg_replace('/\{\{/', '{', $text);
        $text = preg_replace('/\}\}/', '}', $text);

        return $text;
    }
    
}

?>
