<?php

namespace XmlTmpl;

use Nette;
use XMLReader;

/**
 * Provádí obsluhu základních značek
 * @author Lukáš Ambrož
 */
class CoreTags
{
    
    /**
     * Reference na instanci Compiler
     * @var Compiler 
     */
    private $compiler;
    
    /**
     * Mapuje názvy značek na příslušné funkce podle typu značky (otevírací, ...)
     * @var array
     */
    private $tags;
    
    /**
     * ID generované šablony
     * @var string
     */
    private $tmplId;
    
    /**
     * Příznak, zda se zpracování šablony nachází uvnitř formuláře (pro kontrolu)
     * @var array
     */
    private $isForm;
    
    /**
     * Inicializace objektu
     * @param Compiler $compiler Reference na instanci Compiler
     */
    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
        $this->isForm[] = 0;
        
        $this->prepare();
    }
    
    /**
     * Mapuje značky na názvy funkcí, které provedou jejich zpracování
     */
    private function prepare()
    {
        // Řídicí struktury
        $this->tags['if'] = array(NULL, 'tagIf', 'tagEndIf');
        $this->tags['select'] = array(NULL, 'tagSelect', 'tagEndSelect');
        $this->tags['when'] = array(NULL, 'tagWhen', 'tagEndWhen');
        $this->tags['otherwise'] = array(NULL, 'tagOtherwise', 'tagEndOtherwise');
        $this->tags['foreach'] = array(NULL, 'tagForeach', 'tagEndForeach');
        $this->tags['while'] = array(NULL, 'tagWhile', 'tagEndWhile');
        $this->tags['continue'] = array('tagContinue', NULL, NULL);
        $this->tags['break'] = array('tagBreak', NULL, NULL);
        
        // Formuláře
        $this->tags['form'] = array('tagForm', 'tagStartForm', 'tagEndForm');
        $this->tags['formErrors'] = array('tagFormErrors', NULL, NULL);
        $this->tags['formGroup'] = array('tagFormGroup', NULL, NULL);
        $this->tags['formContainer'] = array('tagFormContainer', 'tagStartFormContainer', 'tagEndFormContainer');
        $this->tags['input'] = array('tagInput', NULL, NULL);
        $this->tags['label'] = array('tagLabel', NULL, NULL);
        
        // Bloky, dědičnost šablon, ...
        $this->tags['include'] = array('tagInclude', NULL, NULL);
        $this->tags['define'] = array(NULL, 'tagDefine', 'tagEndDefine');
        $this->tags['insert'] = array('tagInsert', 'tagStartInsert', 'tagEndInsert');
        $this->tags['extends'] = array(NULL, 'tagExtends', 'tagEndExtends');
        $this->tags['remove'] = array(NULL, 'tagRemove', 'tagEndRemove');
        $this->tags['control'] = array('tagControl', NULL, NULL);
        
        // Ostatní
        $this->tags['set'] = array('tagSet', NULL, NULL);
        $this->tags['execute'] = array('tagExecute', NULL, NULL);
    }
    
    /**
     * Hlavní metoda pro zpracování značky
     * @param string $name Jméno značky
     * @param int $type Typ značky (otevírací, ...)
     * @param array $attrs Atributy značky
     * @throws Nette\Templating\FilterException Pokud značka neexistuje nebo je použita nesprávně
     */
    public function processTag($name, $type, array $attrs)
    {
        // Ověření názvu značky
        if (!array_key_exists($name, $this->tags))
            throw new Nette\Templating\FilterException("Unknown tag: $name");
        
        // Ověření správného použití značky
        if (is_null($this->tags[$name][$type]))
            throw new Nette\Templating\FilterException("Inappropriate use of tag: $name");
        
        // Ošetření značek remove, extends
        if ($this->compiler->remove && $name != 'remove')
            return;
        if ($this->compiler->extends) {
            if (!$this->compiler->block && $name != 'define' && $name != 'set' && $name != 'extends' && $name != 'remove')
                return;
        }
        
        // Volání metody pro zpracování značky
        if (!call_user_func(array($this, $this->tags[$name][$type]), $attrs))
            throw new Nette\Templating\FilterException("Inappropriate use of tag: $name");
    }
    
    /**
     * Vygeneruje PHP kód na začátku šablony
     */
    public function processStart()
    {
        $this->tmplId = Nette\Utils\Strings::random();
        
        $res = "\n<?php\n";
        $res .= "f::setTemplate(\$template);\n";
        $res .= "h::setTemplate(\$template);\n";
        $res .= "if (!isset(\$_temp)) \$_temp = new XmlTmpl\\TempStorage();\n";
        $res .= "?>\n";
        
        $this->compiler->start .= $res;
    }
    
    /**
     * Vygeneruje PHP kód na konci šablony
     */
    public function processEnd()
    {
        $res = "\n<?php\n";
        $res .= "if (\$_temp->isIncluded()) \$_temp->setDefinedVars(get_defined_vars());\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
    }
    
    /**
     * Zpracování počáteční značky if
     * @param array $attrs Atributy značky
     * @return bool false, pokud má značka chybně zadány atributy
     */
    private function tagIf($attrs)
    {
        if (!array_key_exists('test', $attrs))
            return false;
        
        $res = "\n<?php if (".$attrs['test']->getPhpExpression()."): ?>\n";
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování koncové značky if
     * @return bool Úspěch operace
     */
    private function tagEndIf()
    {
        $res = "\n<?php endif; ?>\n";
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování počáteční značky select
     * @return bool Úspěch operace
     */
    private function tagSelect()
    {
        return true;
    }
    
    /**
     * Zpracování koncové značky select
     * @return bool Úspěch operace
     */
    private function tagEndSelect()
    {
        if ($this->compiler->lastType == XMLReader::END_ELEMENT && $this->compiler->lastNs === Compiler::CORE_TAGS_NS) {
            if ($this->compiler->lastName === 'when' || $this->compiler->lastName === 'otherwise') {
                $res = "\n<?php endif; ?>\n";
                $this->compiler->appendOutput($res);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Zpracování počáteční značky when
     * @param array $attrs Atributy značky
     * @return bool Úspěch operace
     */
    private function tagWhen($attrs)
    {
        if (!array_key_exists('test', $attrs))
            return false;
        
        if ($this->compiler->lastType != XMLReader::ELEMENT && $this->compiler->lastType != XMLReader::END_ELEMENT)
            return false;
        if ($this->compiler->lastNs !== Compiler::CORE_TAGS_NS)
            return false;
        if ($this->compiler->lastType == XMLReader::ELEMENT && $this->compiler->lastName !== 'select')
            return false;
        if ($this->compiler->lastType == XMLReader::END_ELEMENT && $this->compiler->lastName !== 'when')
            return false;
        
        $type = $this->compiler->lastName === 'select' ? 'if' : 'elseif';
        
        $res = "\n<?php ".$type." (".$attrs['test']->getPhpExpression()."): ?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování koncové značky when
     * @return bool Úspěch operace
     */
    private function tagEndWhen()
    {
        return true;
    }
    
    /**
     * Zpracování počáteční značky otherwise
     * @return bool Úspěch operace
     */
    private function tagOtherwise()
    {
        if ($this->compiler->lastType != XMLReader::END_ELEMENT)
            return false;
        if ($this->compiler->lastNs !== Compiler::CORE_TAGS_NS)
            return false;
        if ($this->compiler->lastName !== 'when')
            return false;
        
        $res = "\n<?php else: ?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování koncové značky otherwise
     * @return bool Úspěch operace
     */
    private function tagEndOtherwise()
    {
        return true;
    }
    
    /**
     * Zpracování počáteční značky foreach
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagForeach($attrs)
    {
        if (!array_key_exists('items', $attrs))
            return false;
        if (!array_key_exists('var', $attrs))
            return false;
        
        $res = "\n<?php\n";
        $res .= "if (isset(\$iterator) && (\$iterator instanceof Nette\\Iterators\\CachingIterator)) \$_temp->pushIterator(\$iterator);\n";
        $res .= "\$iterator = new Nette\\Iterators\\CachingIterator(".$attrs['items']->getPhpExpression().");\n";
        $res .= "foreach (\$iterator as \${".$attrs['var']->getPhpExpression()."}):\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování koncové značky foreach
     * @return bool Úspěch operace
     */
    private function tagEndForeach()
    {
        $res = "<?php\n";
        $res .= "endforeach;\n";
        $res .= "\$iterator = \$_temp->popIterator();\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování počáteční značky while
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagWhile($attrs)
    {
        if (!array_key_exists('test', $attrs))
            return false;
        
        $res = "\n<?php while (".$attrs['test']->getPhpExpression()."): ?>\n";
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování koncové značky while
     * @return bool Úspěch operace
     */
    private function tagEndWhile()
    {
        $res = "\n<?php endwhile; ?>\n";
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky continue
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagContinue($attrs)
    {
        $res = "\n<?php ";
        
        if (array_key_exists('if', $attrs))
            $res .= "if (".$attrs['if']->getPhpExpression().") ";
        
        $res .= "continue; ?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky break
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagBreak($attrs)
    {
        $res = "\n<?php ";
        
        if (array_key_exists('if', $attrs))
            $res .= "if (".$attrs['if']->getPhpExpression().") ";
        
        $res .= "break; ?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky set
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagSet($attrs)
    {
        if (!array_key_exists('var', $attrs))
            return false;
        if (!array_key_exists('value', $attrs))
            return false;
        
        $res = "\n<?php \${".$attrs['var']->getPhpExpression()."} = ".$attrs['value']->getPhpExpression()."; ?>\n";
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky execute
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagExecute($attrs)
    {
        if (!array_key_exists('stmt', $attrs))
            return false;
        
        $res = "\n<?php\n";
        if (array_key_exists('if', $attrs))
            $res .= "if (".$attrs['if']->getPhpExpression().") {\n";
        $res .= $attrs['stmt']->getPhpExpression()."\n";
        if (array_key_exists('if', $attrs))
            $res .= "}\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky form (vykreslení celého formuláře)
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagForm($attrs)
    {
        if (!array_key_exists('form', $attrs))
            return false;
        
        $form = $attrs['form']->getPhpExpression();
        
        $res = "\n<?php\n";
        $res .= "\$_temp->pushForm(is_object(".$form.") ? ".$form." : \$_control[".$form."]);\n";
        
        if (array_key_exists('attr', $attrs))
            $res .= "\$_temp->topForm()->getElementPrototype()->addAttributes(".$attrs['attr']->getPhpExpression().");\n";
        
        $res .= "\$_temp->topForm()->render();\n";
        $res .= "\$_temp->popForm();\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování počáteční značky form
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagStartForm($attrs)
    {
        if (!array_key_exists('form', $attrs))
            return false;
        
        $this->isForm[count($this->isForm) - 1]++;
        
        $form = $attrs['form']->getPhpExpression();
        
        $res = "\n<?php\n";
        $res .= "\$_temp->pushForm(is_object(".$form.") ? ".$form." : \$_control[".$form."]);\n";
        
        if (array_key_exists('attr', $attrs))
            $res .= "\$_temp->topForm()->getElementPrototype()->addAttributes(".$attrs['attr']->getPhpExpression().");\n";
        
        $res .= "\$_temp->topForm()->render('begin');\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování koncové značky form
     * @return bool Úspěch operace
     */
    private function tagEndForm()
    {
        $this->isForm[count($this->isForm) - 1]--;
        
        $res = "\n<?php\n";
        $res .= "\$_temp->popForm()->render('end');\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky formErrors
     * @return bool Úspěch operace
     */
    private function tagFormErrors()
    {
        if ($this->isForm[count($this->isForm) - 1] == 0)
            return false;
        
        $res = "\n<?php\n";
        $res .= "\$_temp->topForm()->getForm()->render('errors');\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky formGroup
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagFormGroup($attrs)
    {
        if (!array_key_exists('name', $attrs))
            return false;
        if ($this->isForm[count($this->isForm) - 1] == 0)
            return false;
        
        $res = "\n<?php\n";
        $res .= "echo \$_temp->topForm()->getForm()->renderer->renderControls(\$_temp->topForm()->getForm()->getGroup(".$attrs['name']->getPhpExpression()."));\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky formContainer
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagFormContainer($attrs)
    {
        if (!array_key_exists('name', $attrs))
            return false;
        if ($this->isForm[count($this->isForm) - 1] == 0)
            return false;
        
        $res = "\n<?php\n";
        $res .= "\$_tmp = \$_temp->topForm()->getForm();\n";
        $res .= "echo \$_temp->topForm()->getForm()->renderer->renderControls(\$_tmp[".$attrs['name']->getPhpExpression()."]);\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování počáteční značky formContainer
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagStartFormContainer($attrs)
    {
        if (!array_key_exists('name', $attrs))
            return false;
        if ($this->isForm[count($this->isForm) - 1] == 0)
            return false;
        
        $res = "\n<?php\n";
        $res .= "\$_tmp = \$_temp->topForm();\n";
        $res .= "\$_temp->pushForm(\$_tmp[".$attrs['name']->getPhpExpression()."]); ?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování koncové značky formContainer
     * @return bool Úspěch operace
     */
    private function tagEndFormContainer()
    {
        $res = "\n<?php \$_temp->popForm(); ?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky input
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagInput($attrs)
    {
        if (!array_key_exists('name', $attrs))
            return false;
        if ($this->isForm[count($this->isForm) - 1] == 0)
            return false;
        
        $res = "\n<?php\n";
        $res .= "\$_tmp = \$_temp->topForm();\n";
        $res .= "echo \$_tmp[".$attrs['name']->getPhpExpression()."]->control";
        
        if (array_key_exists('attr', $attrs))
            $res .= "->addAttributes(".$attrs['attr']->getPhpExpression().")";
        
        $res .= "->render(); ?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky label
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagLabel($attrs)
    {
        if (!array_key_exists('name', $attrs))
            return false;
        if ($this->isForm[count($this->isForm) - 1] == 0)
            return false;
        
        $res = "\n<?php\n";
        $res .= "\$_tmp = \$_temp->topForm();";
        $res .= "echo \$_tmp[".$attrs['name']->getPhpExpression()."]->label";
        
        if (array_key_exists('attr', $attrs))
            $res .= "->addAttributes(".$attrs['attr']->getPhpExpression().")";
        
        $res .= "->render(); ?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování počáteční značky remove
     * @return bool Úspěch operace
     */
    private function tagRemove()
    {
        $this->compiler->remove++;
        
        return true;
    }
    
    /**
     * Zpracování koncové značky remove
     * @return bool Úspěch operace
     */
    private function tagEndRemove()
    {
        $this->compiler->remove--;
        
        return true;
    }
    
    /**
     * Zpracování značky control
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagControl($attrs)
    {
        if (!array_key_exists('name', $attrs))
            return false;
        
        $args = array_key_exists('args', $attrs) ? $attrs['args']->getPhpExpression() : 'array()';
        
        $res = "\n<?php call_user_func_array(array(\$_control->getComponent(".$attrs['name']->getPhpExpression()."), 'render'";
        
        if (array_key_exists('mode', $attrs))
                $res .= ".Nette\\Utils\\Strings::firstUpper(".$attrs['mode']->getPhpExpression().")";
        
        $res .= "), ".$args."); ?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování počáteční značky define
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagDefine($attrs)
    {
        if (!array_key_exists('name', $attrs))
            return false;
        // Kontrola, jestli název bloku neobsahuje výrazy nebo není prázdný
        $name = $attrs['name']->getString();
        if ($name === NULL || strlen($name) == 0)
            return false;
        if ($this->compiler->block)
            return false;
        
        $this->compiler->block = true;
        // Resetování příznaku, zda se zpracování nachází uvnitř formuláře
        $this->isForm[] = 0;
        
        // Vytvoření názvu funkce
        $fname = '_block_'.$name.'_'.$this->tmplId;
        
        $res = "\n<?php\n";
        $res .= "if (\$_temp->isBlock()) throw new Nette\\InvalidStateException('Nested blocks are not supported.');\n";
        $res .= "\$_temp->defineBlock('".$name."', '".$fname."', ".($this->compiler->extends ? 'true' : 'false').");\n";
        $res .= "function ".$fname."(\$args) {\n";
        $res .= "extract(\$args);\n";
        $res .= "\$_temp->setBlock();\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování koncové značky define
     * @return bool Úspěch operace
     */
    private function tagEndDefine()
    {
        $res = "\n<?php\n}\n?>\n";
        
        $this->compiler->appendOutput($res);
        
        $this->compiler->block = false;
        // Obnovení příznaku, zda se zpracování nachází uvnitř formuláře
        array_pop($this->isForm);
        
        return true;
    }
    
    /**
     * Zpracování značky insert
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagInsert($attrs)
    {
        if (!array_key_exists('name', $attrs))
            return false;
        
        $name = $attrs['name']->getPhpExpression();
        
        // Argumenty pro volání funkce reprezentující blok
        $args = array_key_exists('args', $attrs) ? $attrs['args']->getPhpExpression().' + ' : '';
        $args .= 'get_defined_vars()';
        
        $res = "\n<?php\n";
        $res .= "if (\$_temp->getBlockFunction(".$name.") === NULL) throw new Nette\\InvalidStateException('Block not defined.');\n";
        $res .= "call_user_func(\$_temp->getBlockFunction(".$name."), ".$args.");\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování počáteční značky insert
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagStartInsert($attrs)
    {
        if (!array_key_exists('name', $attrs))
            return false;
        
        $name = $attrs['name']->getPhpExpression();
        
        // Argumenty pro volání funkce reprezentující blok
        $args = array_key_exists('args', $attrs) ? $attrs['args']->getPhpExpression().' + ' : '';
        $args .= 'get_defined_vars()';
        
        $res = "\n<?php\n";
        $res .= "if (\$_temp->getBlockFunction(".$name.") !== NULL) call_user_func(\$_temp->getBlockFunction(".$name."), ".$args.");\n";
        $res .= "else {\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování koncové značky insert
     * @return bool Úspěch operace
     */
    private function tagEndInsert()
    {
        $res = "\n<?php\n";
        $res .= "}\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování značky include
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagInclude($attrs)
    {
        if (!array_key_exists('src', $attrs))
            return false;
        
        $args = array_key_exists('args', $attrs) ? $attrs['args']->getPhpExpression().' + ' : '';
        $args .= 'get_defined_vars()';
        
        $res = "\n<?php\n";
        $res .= "\$_temp->setIncluded();\n";
        $res .= "Nette\\Latte\\Macros\\CoreMacros::includeTemplate(".$attrs['src']->getPhpExpression().", ".$args.", \$template)->render();\n";
        $res .= "extract(\$_temp->getDefinedVars());\n";
        $res .= "\$_temp->unsetIncluded();\n";
        $res .= "?>\n";
        
        $this->compiler->appendOutput($res);
        
        return true;
    }
    
    /**
     * Zpracování počáteční značky extends
     * @param array $attrs Atributy
     * @return bool Úspěch operace
     */
    private function tagExtends($attrs)
    {
        if ($this->compiler->extends)
            return false;
        
        // Reset potřebných nastavení
        $this->compiler->extends = true;
        $this->compiler->block = false;
        $this->compiler->blocks = $this->compiler->body = '';
        $this->isForm = array(0);
        
        $this->compiler->start .= "<?php\n";
        $this->compiler->start .= "if (\$_temp->isIncluded()) throw new Nette\\InvalidStateException('Including descendant template.');\n";
        $this->compiler->start .= "if (!\$_temp->checkDescendantTmpl('".$this->tmplId."')) throw new Nette\\InvalidStateException('Extending descendant template.');\n";
        $this->compiler->start .= "?>\n";
        
        if (!array_key_exists('layout', $attrs))
            $this->compiler->body .= "<?php Nette\\Latte\\Macros\\CoreMacros::includeTemplate(\$_control->findLayoutTemplateFile(), get_defined_vars(), \$template)->render(); ?>";
        else
            $this->compiler->body .= "<?php Nette\\Latte\\Macros\\CoreMacros::includeTemplate(".$attrs['layout']->getPhpExpression().", get_defined_vars(), \$template)->render(); ?>";
        
        return true;
    }
    
    /**
     * Zpracování koncové značky extends
     * @return bool Úspěch operace
     */
    private function tagEndExtends()
    {
        // Ignorování zbytku šablony
        $this->compiler->remove++;
        
        return true;
    }
    
}

?>
