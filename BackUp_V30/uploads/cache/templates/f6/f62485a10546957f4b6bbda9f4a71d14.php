<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* profile/sidebar.twig.html */
class __TwigTemplate_71c333849e893785c0b01d079cecddcb extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 10
        yield "
";
        // line 11
        yield ($context["userPhoto"] ?? null);
        yield "

<div class=\"column-no-break\">
    <h4>";
        // line 14
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('__')->getCallable()("Personal"), "html", null, true);
        yield "</h4>

    <ul class='moduleMenu'>
        <li>
            <a class=\"";
        // line 18
        yield (((($context["subpage"] ?? null) == "Overview")) ? ("font-bold") : (""));
        yield " underline\" href=\"";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["absoluteURL"] ?? null), "html", null, true);
        yield "/index.php?q=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["q"] ?? null), "html", null, true);
        yield "&gibbonPersonID=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["gibbonPersonID"] ?? null), "html", null, true);
        yield "&search=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["search"] ?? null), "html", null, true);
        yield "&allStaff=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["allStaff"] ?? null), "html", null, true);
        yield "&subpage=Overview\">";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('__')->getCallable()("Overview"), "html", null, true);
        yield "</a>
        </li>

        <li>
            <a class=\"";
        // line 22
        yield (((($context["subpage"] ?? null) == "Personal")) ? ("font-bold") : (""));
        yield " underline\" href=\"";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["absoluteURL"] ?? null), "html", null, true);
        yield "/index.php?q=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["q"] ?? null), "html", null, true);
        yield "&gibbonPersonID=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["gibbonPersonID"] ?? null), "html", null, true);
        yield "&search=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["search"] ?? null), "html", null, true);
        yield "&allStaff=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["allStaff"] ?? null), "html", null, true);
        yield "&subpage=Personal\">";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('__')->getCallable()("Personal"), "html", null, true);
        yield "</a>
        </li>

        <li>
            <a class=\"";
        // line 26
        yield (((($context["subpage"] ?? null) == "Family")) ? ("font-bold") : (""));
        yield " underline\" href=\"";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["absoluteURL"] ?? null), "html", null, true);
        yield "/index.php?q=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["q"] ?? null), "html", null, true);
        yield "&gibbonPersonID=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["gibbonPersonID"] ?? null), "html", null, true);
        yield "&search=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["search"] ?? null), "html", null, true);
        yield "&allStaff=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["allStaff"] ?? null), "html", null, true);
        yield "&subpage=Family\">";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('__')->getCallable()("Family"), "html", null, true);
        yield "</a>
        </li>

        ";
        // line 29
        if (($context["canViewEmergency"] ?? null)) {
            // line 30
            yield "            <li>
                <a class=\"";
            // line 31
            yield (((($context["subpage"] ?? null) == "Emergency Contacts")) ? ("font-bold") : (""));
            yield " underline\" href=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["absoluteURL"] ?? null), "html", null, true);
            yield "/index.php?q=";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["q"] ?? null), "html", null, true);
            yield "&gibbonPersonID=";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["gibbonPersonID"] ?? null), "html", null, true);
            yield "&search=";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["search"] ?? null), "html", null, true);
            yield "&allStaff=";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["allStaff"] ?? null), "html", null, true);
            yield "&subpage=Emergency Contacts\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('__')->getCallable()("Emergency Contacts"), "html", null, true);
            yield "</a>
            </li>
        ";
        }
        // line 34
        yield "    </ul>

    <h4>";
        // line 36
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('__')->getCallable()("School"), "html", null, true);
        yield "</h4>

    <ul class='moduleMenu'>
        <li>
            <a class=\"";
        // line 40
        yield (((($context["subpage"] ?? null) == "Activities")) ? ("font-bold") : (""));
        yield " underline\" href=\"";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["absoluteURL"] ?? null), "html", null, true);
        yield "/index.php?q=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["q"] ?? null), "html", null, true);
        yield "&gibbonPersonID=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["gibbonPersonID"] ?? null), "html", null, true);
        yield "&search=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["search"] ?? null), "html", null, true);
        yield "&allStaff=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["allStaff"] ?? null), "html", null, true);
        yield "&subpage=Activities\">";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('__')->getCallable()("Activities"), "html", null, true);
        yield "</a>
        </li>

        <li>
            <a class=\"";
        // line 44
        yield (((($context["subpage"] ?? null) == "Facilities")) ? ("font-bold") : (""));
        yield " underline\" href=\"";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["absoluteURL"] ?? null), "html", null, true);
        yield "/index.php?q=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["q"] ?? null), "html", null, true);
        yield "&gibbonPersonID=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["gibbonPersonID"] ?? null), "html", null, true);
        yield "&search=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["search"] ?? null), "html", null, true);
        yield "&allStaff=";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["allStaff"] ?? null), "html", null, true);
        yield "&subpage=Facilities\">";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('__')->getCallable()("Facilities"), "html", null, true);
        yield "</a>
        </li>

        ";
        // line 47
        if (($context["canViewTimetable"] ?? null)) {
            // line 48
            yield "            <li>
                <a class=\"";
            // line 49
            yield (((($context["subpage"] ?? null) == "Timetable")) ? ("font-bold") : (""));
            yield " underline\" href=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["absoluteURL"] ?? null), "html", null, true);
            yield "/index.php?q=";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["q"] ?? null), "html", null, true);
            yield "&gibbonPersonID=";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["gibbonPersonID"] ?? null), "html", null, true);
            yield "&search=";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["search"] ?? null), "html", null, true);
            yield "&allStaff=";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["allStaff"] ?? null), "html", null, true);
            yield "&subpage=Timetable\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('__')->getCallable()("Timetable"), "html", null, true);
            yield "</a>
            </li>
        ";
        }
        // line 52
        yield "    </ul>

    ";
        // line 54
        if (($context["hooks"] ?? null)) {
            // line 55
            yield "    <h4>";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('__')->getCallable()("Modules"), "html", null, true);
            yield "</h4>

    <ul class='moduleMenu'>
        ";
            // line 58
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["hooks"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["hook"]) {
                // line 59
                yield "        <li>
            <a class=\"";
                // line 60
                yield (((($context["currentHook"] ?? null) == CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["hook"], "options", [], "any", false, false, false, 60), "sourceModuleName", [], "any", false, false, false, 60))) ? ("font-bold") : (""));
                yield " underline\" href=\"";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["absoluteURL"] ?? null), "html", null, true);
                yield "/index.php?q=";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["q"] ?? null), "html", null, true);
                yield "&gibbonPersonID=";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["gibbonPersonID"] ?? null), "html", null, true);
                yield "&search=";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["search"] ?? null), "html", null, true);
                yield "&allStaff=";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["allStaff"] ?? null), "html", null, true);
                yield "&subpage=&hook=";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["hook"], "options", [], "any", false, false, false, 60), "sourceModuleName", [], "any", false, false, false, 60), "html", null, true);
                yield "&gibbonHookID=";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["hook"], "gibbonHookID", [], "any", false, false, false, 60), "html", null, true);
                yield "\">";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((((CoreExtension::getAttribute($this->env, $this->source, $context["hook"], "name", [], "any", true, true, false, 60) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["hook"], "name", [], "any", false, false, false, 60)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["hook"], "name", [], "any", false, false, false, 60)) : (CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["hook"], "options", [], "any", false, false, false, 60), "sourceModuleName", [], "any", false, false, false, 60))), "html", null, true);
                yield "</a>
        </li>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['hook'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 63
            yield "    </ul>
    ";
        }
        // line 65
        yield "</div>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "profile/sidebar.twig.html";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  253 => 65,  249 => 63,  224 => 60,  221 => 59,  217 => 58,  210 => 55,  208 => 54,  204 => 52,  186 => 49,  183 => 48,  181 => 47,  163 => 44,  144 => 40,  137 => 36,  133 => 34,  115 => 31,  112 => 30,  110 => 29,  92 => 26,  73 => 22,  54 => 18,  47 => 14,  41 => 11,  38 => 10,);
    }

    public function getSourceContext()
    {
        return new Source("{#<!--
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This is a Gibbon template file, written in HTML and Twig syntax.
For info about editing, see: https://twig.symfony.com/doc/2.x/
-->#}

{{ userPhoto|raw }}

<div class=\"column-no-break\">
    <h4>{{ __('Personal') }}</h4>

    <ul class='moduleMenu'>
        <li>
            <a class=\"{{ subpage == 'Overview' ? 'font-bold' }} underline\" href=\"{{ absoluteURL }}/index.php?q={{ q }}&gibbonPersonID={{ gibbonPersonID }}&search={{ search }}&allStaff={{ allStaff }}&subpage=Overview\">{{ __('Overview') }}</a>
        </li>

        <li>
            <a class=\"{{ subpage == 'Personal' ? 'font-bold' }} underline\" href=\"{{ absoluteURL }}/index.php?q={{ q }}&gibbonPersonID={{ gibbonPersonID }}&search={{ search }}&allStaff={{ allStaff }}&subpage=Personal\">{{ __('Personal') }}</a>
        </li>

        <li>
            <a class=\"{{ subpage == 'Family' ? 'font-bold' }} underline\" href=\"{{ absoluteURL }}/index.php?q={{ q }}&gibbonPersonID={{ gibbonPersonID }}&search={{ search }}&allStaff={{ allStaff }}&subpage=Family\">{{ __('Family') }}</a>
        </li>

        {% if canViewEmergency %}
            <li>
                <a class=\"{{ subpage == 'Emergency Contacts' ? 'font-bold' }} underline\" href=\"{{ absoluteURL }}/index.php?q={{ q }}&gibbonPersonID={{ gibbonPersonID }}&search={{ search }}&allStaff={{ allStaff }}&subpage=Emergency Contacts\">{{ __('Emergency Contacts') }}</a>
            </li>
        {% endif %}
    </ul>

    <h4>{{ __('School') }}</h4>

    <ul class='moduleMenu'>
        <li>
            <a class=\"{{ subpage == 'Activities' ? 'font-bold' }} underline\" href=\"{{ absoluteURL }}/index.php?q={{ q }}&gibbonPersonID={{ gibbonPersonID }}&search={{ search }}&allStaff={{ allStaff }}&subpage=Activities\">{{ __('Activities') }}</a>
        </li>

        <li>
            <a class=\"{{ subpage == 'Facilities' ? 'font-bold' }} underline\" href=\"{{ absoluteURL }}/index.php?q={{ q }}&gibbonPersonID={{ gibbonPersonID }}&search={{ search }}&allStaff={{ allStaff }}&subpage=Facilities\">{{ __('Facilities') }}</a>
        </li>

        {% if canViewTimetable %}
            <li>
                <a class=\"{{ subpage == 'Timetable' ? 'font-bold' }} underline\" href=\"{{ absoluteURL }}/index.php?q={{ q }}&gibbonPersonID={{ gibbonPersonID }}&search={{ search }}&allStaff={{ allStaff }}&subpage=Timetable\">{{ __('Timetable') }}</a>
            </li>
        {% endif %}
    </ul>

    {% if hooks %}
    <h4>{{ __('Modules') }}</h4>

    <ul class='moduleMenu'>
        {% for hook in hooks %}
        <li>
            <a class=\"{{ currentHook == hook.options.sourceModuleName ? 'font-bold' }} underline\" href=\"{{ absoluteURL }}/index.php?q={{ q }}&gibbonPersonID={{ gibbonPersonID }}&search={{ search }}&allStaff={{ allStaff }}&subpage=&hook={{ hook.options.sourceModuleName }}&gibbonHookID={{ hook.gibbonHookID }}\">{{ hook.name ?? hook.options.sourceModuleName }}</a>
        </li>
        {% endfor %}
    </ul>
    {% endif %}
</div>
", "profile/sidebar.twig.html", "C:\\xampp\\htdocs\\gibbon_28\\modules\\Staff\\templates\\profile\\sidebar.twig.html");
    }
}
