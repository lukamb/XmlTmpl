# XmlTmpl
XmlTmpl is an extension for Nette PHP framework, which allows the definition of page templates in XML language using its own set of special tags (similar to JavaServer Faces). It replaces the native template engine Latte. Usage of XML is more convenient because of well known XHTML syntax and the template code is then more consistent. Extension also allows you to define your own tags and assign them the desired functionality in PHP. Sample application using the extension is also included.
Sample template code:
<n:if test="{$test}">
	<a href="{f::link('Presenter:action')}">Odkaz</a>
</n:if>
