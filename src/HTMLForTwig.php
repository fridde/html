<?php

namespace Fridde;

class HTMLForTwig
{

    private $TWIG;
    private $var;
    private $Template = "index.twig";
    private $CssFiles = [];
    private $LiteralCss = [];
    public $Title;
    public $head;
    public $body;
    private $includables;
    public $includables_file_name = "includables.ini";

    const INCLUDE_TYPES =  ["abbreviation" => 0, "adress" => 1, "literal" => 2];

    const STD_CSS = "stylesheet.css";

    function __construct ($template_dir = null)
    {
        if(empty($template_dir)){
            if(empty($GLOBALS["APP_DIR"])){
                throw new \Exception("No template directory given.");
            } else {
                $template_dir = $GLOBALS["APP_DIR"] . "/templates";
            }
        }
        $loader = new Twig_Loader_Filesystem($template_dir);
        $this->TWIG = new Twig_Environment($loader);
        $this->TWIG->addGlobal("A", $this);
    }

    public function addCss($css = null, $type = "abbreviation")
    {
        $css = $css ?? self::STDSTD_CSS;
        $css_array = (array) $css;
        if(!is_integer($type)){
            $type = self::INCLUDE_TYPES[$type];
        }
        foreach($css_array as $single_css){
            if($type == 0){ //abbreviation
                $single_css = $this->getIncludableAddres($single_css);
            }
            if($type <= 1){ //i.e. abbreviation or adress
                $this->CssFiles[] = $single_css;
            }
            if($type == 2){
                $this->LiteralCss[] = $single_css;
            }
        }
        return $this;
    }

    private function getIncludableAdress($abbreviation)
    {
        if(empty($this->includables)){
            $this->setIncludablesFromFile();
        }
        $category = array_filter($this->includables, function($cat) use ($abbreviation){
            return !empty($cat[$abbreviation]);
        });
        $category = array_values($category);


        if(count($category) > 1){
            throw new \Exception("The abbreviation $abbreviation was not unique. Check the includable file.");
        } elseif(count($category) === 0) {
            throw new \Exception("The abbreviation $abbreviation could not be found. Check the includable file.");
        } else {
            return $category[0][$abbreviation];
        }
    }

    private function setIncludablesFromFile($file_name = null)
    {
        $file_name = $file_name ?? $this->includables_file_name;
        if(is_readable($file_name)){
            $this->includables = parse_ini_file($file_name, true);
        } else {
            throw new \Exception("No file for includables found.");
        }
    }


    public function render($echo = true)
    {
        $this->collectAllVariables();
        echo $this->TWIG->render($this->Template, $this->var);
    }

    private function collectAllVariables()
    {
        $var["css"]["files"] = $this->css_files;
        $var["css"]["literals"] = $this->literal_css;
        $var["title"] = $this->title;

        $this->var = $var;

        return $this;
    }


    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }


    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->Title;
    }

    public function getCssFiles()
    {
        return $this->CssFiles;
    }

    public function getLiteralCss()
    {
        return $this->LiteralCss;
    }

    public function addForm()
    {
        //fields, action
        $default = ["id" => substr(uniqid(), 0, 5), "method" => "post"];
        $options = func_get_arg(0) + $default;

        $options["atts"]["method"] = $options["method"];
        $options["method"] = null;

        $atts = array_filter($atts);
        $this->var["form"][$id] = $options;
        return $this;
    }



    public function addInput()
    {
        // label, type, group, options,
    }

    public function addSelect()
    {
        //label, options
    }


//#########################################################################
//#########################################################################
//#########################################################################


    public function addButton()
    {
        $def = ["node" => null, "content" => "", "type" => "button", "form_id" => "",
        "formaction" => "", "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $atts["type"] = $type;
        if($form_id != ""){
            $atts["form"] = $form_id;
        }
        if($formaction != "" && $type == "submit"){
            $atts["formaction"] = $formaction;
        }
        $button = $this->add($node, "button", $content, $atts);
        return $button;
    }


    public function addDiv()
    {
        $def = ["node" => null, "class" => "", "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $atts["class"] = (isset($atts["class"])) ? $atts["class"] : $class;

        $div = $this->add($node, "div", "", $atts);

        return $div;
    }

    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */


    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    public function addList()
    {
        $def = ["node" => null, "elements" => array(), "type" => "ul", "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $list = $this->add($node, $type, "", $atts);
        $list_elements = array();
        foreach($elements as $element){
            $content = (is_array($element) ? $element[0] : $element);
            $atts = (is_array($element) ? $element[1] : array());
            $list_elements[] = $this->add($list, "li", $content, $atts);
        }
        return $list_elements;
    }
    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    * [["atts" => [], "text" => "Food", "children" => [], "value" => ""], [], []]
    *
    * @return [type] [name] [description]
    */
    public function addNestedList($node, $list, $atts = [], $with_input = false)
    {
        $ul = $this->add($node, "ul", "", $atts);
        foreach($list as $element){
            $text = $element["text"];
            $local_atts = $element["atts"] ?? [];
            $element_id = $local_atts["data-id"] ?? false;
            $paranthesis = $element_id ? "($element_id) " : "";
            $li = $this->add($ul, "li", $paranthesis . $element["text"], $local_atts);

            $children = $element["children"] ?? [];
            if(count($children) > 0){
                $this->addNestedList($li, $children, $local_atts, $with_input);
            }
            elseif($with_input) {
                $this->add($li, "input", "", ["value" => $element["value"]]);
            }
        }
    }

    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    public function addIframe()
    {
        $def = ["node" => null, "src" => null, "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $atts = array_merge($atts, ["src" => $src]);
        $iframe = $this->add($node, "iframe", "", $atts);
        return $iframe;
    }

    public function addImg()
    {
        $def = ["node" => null, "src" => null, "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $atts = array_merge($atts, ["src" => $src]);
        $img = $this->add($node, "img", "", $atts);
        return $img;
    }

    /**
    * [Summary].
    *
    * [Description]
    *
    * @param mixed $name If $name is a string, it will be used as the "name"-attribute of the input.
    * If $name is an array, a label given in the second element of $name will be created, too.
    *
    * @return [type] [name] [description]
    */
    public function OldAddInput()
    {
        $def = ["node" => null, "name" => null, "type" => "text", "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $add_label = false;
        $label_first = !in_array($type, ["radio", "checkbox"]);
        $random_nr = rand(0,9999);

        if(is_array($name) && count($name) == 2){
            $label = $name[1];
            $name = $name[0];
            if(isset($atts["id"])){
                $id = $atts["id"];
            }
            elseif(isset($atts[0]) && is_array($atts[0]) && isset($atts[0][1])){ //id already exists
                $id = $atts[0][1];
            }
            else { // we have to create an id
                $id = $name . "_" . $random_nr;
            }
            $add_label = true;
        }
        $atts["type"] = $type;

        if($type != "submit"){
            $atts["name"] = $name;
        }
        else if (!isset($atts["value"])) {
            $atts["value"] = "Submit";
        }
        if($add_label && $label_first){
            $this->add($node, "label", $label, ["for" => $id]);
        }
        $input = $this->add($node, "input", "", $atts);
        if($add_label && !$label_first){
            $this->add($node, "label", $label, ["for" => $id]);
        }

        return $input;
    }
    /**
    * Implements a slider defined in jQuery UI.
    *
    * This function has to be implemented with the corresponding Javascript code
    *
    * @param [Type] $ [Argument description]
    *
    * @return [type] [name] [description]
    */
    public function addSlider()
    {
        $options = ["initial_value" => 0, "min_max" => [0, 50], "layout" => "below", "column" => null, "row_id" => null, "label" => null];
        $def = ["node" => null, "atts" => [], "options" => $options];
        extract($this->prepareForExtraction($def, func_get_args()));

        $atts["class"] = "input-slider";
        $atts["data-min"] = $options["min_max"][0];
        $atts["data-max"] = $options["min_max"][1];
        $atts["value"] = $options["initial_value"];

        if(isset($options["column"])){
            $atts["data-column"] = $options["column"];
            if(isset($options["row_id"])){
                $slider_id = $options["column"] . "_" . $options["row_id"];  // standard case for a table-updater-slider
            }
            elseif(isset($atts["id"])){
                $slider_id = $atts["id"];  // if the id is given via the attributes passed to this function
            }
            elseif(isset($atts[0][1])){
                $slider_id = $atts[0][1]; // if the attribute-id is passed via shortform ["class", "id"]
            }
        }
        $slider_id = $slider_id ?? rand(0,999); // last backup if everything else fails
        $atts["id"] = $slider_id;

        $slider_counter_id = "slider_counter_" . $slider_id;
        $atts["data-slider-counter-id"] = $slider_counter_id;
        // the slider_counter_id will be used later to ensure that the counter is updated by JS

        if(isset($options["label"])){
            $this->add($node, "label", $options["label"], ["for" => $slider_id]);
        }
        $this->add($node, "div", "", $atts);
        /* Adding the label that changes if the slider is changed.
        The switch case is to ensure that more layouts can be added later.	*/
        switch($options["layout"]){
            case "below":
            $this->add($node, "div", $options["initial_value"], [["slider-value", $slider_counter_id]]);
            break;
        }
    }


    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    public function OldAddSelect()
    {
        $def = ["node" => null, "label_and_name" => null, "select_options" => [] , "atts" => []];
        extract($this->prepareForExtraction($def, func_get_args()));
        if(is_array($label_and_name)){
            $name = $label_and_name[0];
            $label = $label_and_name[1] ?? $name;
            $id = $name . "_" . rand(0,999);
            $id = $atts[0][1] ?? $id;
            $id = $atts["id"] ?? $id;
            $this->add($node, "label", $label, ["for" => $id]);
            $atts["id"] = $id;
        }
        else {
            $name = $label_and_name;
        }

        $atts["name"] = $name;
        $atts["data-column"] = $name;

        $select = $this->add($node, "select", "", $atts);
        /* options for the select can be given as ["option_value_1", "option_value_2", ...]
        OR [["option_text_1", "option_value_1"], ["option_text_2", "option_value_2"], ...]
        OR [["option_text_1", "option_value_1", [option attributes 1]], ["option_text_2", "option_value_2", [option attributes 2]], ...]
        */
        $select_options = array_map(function($v){return (array) $v;}, $select_options); // ensuring that all elements are arrays

        $select_array = array();
        foreach($select_options as $option){
            // given as [option_text, option_value, atts]. If option_value is an empty string, it is assumed to be equal option_text
            $option_text = $option[0];
            $option_value = $option[1] ?? $option_text;
            $option_atts = $option[2] ?? [];
            // will add the value if and only if the value was not specified via the second or third parameter yet
            $option_atts = $option_atts + ["value" => $option_value];

            $select_array[] = $this->add($select, "option", $option_text, $option_atts);
        }
        return $select_array;
    }

    public function addCheckboxes()
    {
        $def = ["node" => null, "name" => null, "select_options" => [] , "checked" => array(), "atts" => array(), "options" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $fieldset = $this->add($node, "fieldset", "", $atts);
        $fieldset_array = array();
        $name .= '[]';
        foreach($select_options as $option){
            $option_text = $option[0];
            $option_value = (isset($option[1]) &&  $option[1] != "") ? $option[1] : $option_text;
            $option_atts = (isset($option[2])) ? $option[2] : ["value" => $option_value];

            if(in_array($option_value, $checked)){
                $option_atts[] = "checked";
            }
            //$def = ["node" => null, "name" => null, "type" => "text", "atts" => array()];
            $fieldset_array[] = $this->addInput($fieldset, [$name, $option_text], "checkbox", $option_atts);
        }

        return $fieldset_array;
    }

    public function addRadio()
    {
        $def = ["node" => null, "name" => null, "select_options" => [] , "selected" => null, "atts" => array(), "options" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $common_atts = $atts;
        $col_name = $name;
        $name = $name . "_" . rand(0,9999);
        $i = 0;
        foreach($select_options as $option_text => $option_value){
            $option_atts = array();
            $is_selected = false;
            if($selected === $option_value){
                $is_selected = true;
            }
            elseif(is_null($selected) && array_search($option_value, $select_options) === 0){ // default: first element is selected
                $is_selected = true;
            }
            if($is_selected){
                $option_atts[] = "checked";
            }
            $option_atts["value"] = $option_value;
            $option_atts["data-column"] = $col_name;
            $atts = array_merge($common_atts, $option_atts);
            $radio_input[] = $this->addInput($node, [$name, $option_value], "radio", $atts);
            $this->add($node, "br");
            $i++;
        }

        return $radio_input;
    }

    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    public function addSingleCss()
    {
        $def = ["arg0" => null, "is_css_content" => false, "node" => null];
        extract($this->prepareForExtraction($def, func_get_args()));

        $includables = $this->includables;

        $is_local = isset($includables["css_local"][$arg0]);
        $is_remote = isset($includables["css_remote"][$arg0]);
        $is_file = substr($arg0, -4) == ".css";

        $file_name = false;
        $content = "";
        $node = (!$node ? $this->head : $node);

        if($is_local){
            $file_name = $includables["css_local"][$arg0];
        }
        else if($is_remote){
            $file_name = $includables["css_remote"][$arg0];
        }
        else if($is_file){
            $file_name = $arg0;
        }
        else if($is_css_content){
            $content = $arg0;
        }
        else {
            throw new \Exception("Given argument to addCss() is neither valid abbreviation given in includables.ini OR a valid css-file OR actual style content ");
        }

        if(!$is_css_content){
            $atts = ["rel" => "stylesheet", "type" => "text/css", "href" => $file_name];
            $tag = "link";
        }
        else {
            $atts = array();
            $tag = "style";
        }
        $this->add($node, $tag, $content, $atts);
    }



    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    //file_name or abbreviation, is_verbatim,
    public function addSingleJs()
    {
        $def = ["arg0" => null, "is_script_content" => false, "node" => null];
        extract($this->prepareForExtraction($def, func_get_args()));

        $includables = $this->includables;

        $is_local = isset($includables["js_local"][$arg0]);
        $is_remote = isset($includables["js_remote"][$arg0]);
        $is_file = substr($arg0, -3) == ".js";

        $file_name = false;
        $content = "";
        $atts = ["type" => "text/javascript"];
        $node = (!$node ? $this->head : $node);

        if($is_local){
            $file_name = $includables["js_local"][$arg0];
        }
        else if($is_remote){
            $file_name = $includables["js_remote"][$arg0];
        }
        else if($is_file){
            $file_name = $arg0;
        }
        else if($is_script_content){
            $content = $arg0;
        }
        else {
            throw new \Exception("Given argument to addJs() is neither valid abbreviation given in includables.ini OR a valid js-file OR actual script content ");
        }

        if(!$is_script_content){
            $atts["src"] = $file_name;
        }
        $this->add($node, "script", $content, $atts);
    }
    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    public function addJs()
    {
        $def = ["arg0" => null, "is_script_content" => false, "node" => null];
        extract($this->prepareForExtraction($def, func_get_args()));

        $js_array = array();
        if(is_array($arg0)){
            $js_array = $arg0;
        }
        else {
            $js_array = [[$arg0, $is_script_content, $node]];
        }
        foreach($js_array as $single_js){
            if(is_array($single_js)){
                $single_arg0 = $single_js[0];
                $single_is_script_content = (isset($single_js[1]) ? $single_js[1] : false);
                $single_node = (isset($single_js[2]) ? $single_js[2] : null);
            }
            else {
                $single_arg0 = $single_js;
                $single_is_script_content = false;
                $single_node = null;
            }
            $this->addSingleJs($single_arg0, $single_is_script_content, $single_node);
        }
    }

    /**
    * SUMMARY OF addTable
    *
    * DESCRIPTION
    *
    * @param TYPE ($array, $id = "sortable", $class = "display stripe") ARGDESCRIPTION
    *
    * @return TYPE NAME DESCRIPTION
    */
    public function addTable()
    {
        $def = ["node" => null, "array" => null, "headers" => array(), "atts" => array(), "options" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $table = $this->add($node, "table", "", $atts);

        if(count($array) == 0 || count(reset($array)) == 0) {
            throw new \Exception("Empty array given to addTable()");
        }

        // if the first row has no keys, we won't need a header
        if(count($headers) == count(reset($array))){
            $col_names = $headers;
        }
        else if(count($headers) > 0){
            throw new \Exception("Number of headers given for addTable() should match number of columns in table");
        }
        else {
            $col_names = array_keys(reset($array));
        }

        $all_numeric = count(array_filter($col_names, function($k){return !is_numeric($k);})) == 0;
        array_walk($col_names, function(&$k){$k = (is_numeric($k)?"":$k);});

        if(!$all_numeric){
            $header = $this->add($table, "thead");
            $header_tr = $this->add($header, "tr");
            foreach($col_names as $col_name){
                $this->add($header_tr, "th", $col_name);
            }
        }
        $tbody = $this->add($table, "tbody");
        foreach($array as $row_key => $row){
            $tr = $this->add($tbody, "tr");
            foreach($row as $col_key => $cell){
                $this->add($tr, "td", $cell);
            }
        }
        return $table;
    }

    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    public function addTextarea()
    {
        $def = ["node" => null, "content" => "", "name" => "", "rows" => "4", "cols" => "50", "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        if(is_array($name) && count($name) == 2){
            $label = $name[1];
            $name = $name[0];
            if(isset($atts["id"])){
                $id = $atts["id"];
            }
            elseif(isset($atts[0][1])){ //id already exists
                $id = $atts[0][1];
            }
            else { // we have to create an id
                $id = $name . "_" . rand(0,999);
            }
            $this->add($node, "label", $label, ["for" => $id]);
        }

        if(is_array($content)){
            $atts["placeholder"] = reset($content);
            $content = "";
        }
        $atts["rows"] = $rows;
        $atts["cols"] = $cols;
        if($name != ""){
            $atts["name"] = $name;
        }

        $textarea = $this->add($node, "textarea", $content, $atts);
        return $textarea;
    }

    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    public function addFontAwesome()
    {

        $def = ["node" => null, "icon_name" => null, "size" => "", "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $class = "fa fa-" . $icon_name;
        $class .= ($size != "" ? " fa-" . $size : "");
        $class .= (isset($atts["class"]) ? " " . $class : "");
        $atts["class"] = $class;

        $icon = $this->add($node, "i", "", $atts);
        return $icon;
    }

    /**
    * [Summary].
    *
    * [Description] See https://api.jqueryui.com/theming/icons/
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    public function addUicon()
    {
        $def = ["node" => null, "icon_name" => null, "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        $class = "ui-icon ui-icon-" . $icon_name;
        $class .= (isset($atts["class"]) ? " " . $class : "");
        $atts["class"] = $class;

        $icon = $this->add($node, "span", "", $atts);
        return $icon;
    }
    /**
    * SUMMARY OF create_bootstrap_navbar
    *
    * DESCRIPTION
    *
    * @param TYPE ($nav_args) ARGDESCRIPTION
    *
    * @return TYPE NAME DESCRIPTION
    */

    public function addBsNav()
    {
        /* will return an array with a the matching arguments for a bootstrap-navbar
        the incoming arguments should be given as following
        0: (string) type of navbar. Possible types: "" (for default), fixed (for fixed header)
        $param array link_array: in the form of "Name to Show" => "link to lead to"
        If a menu-item should have a dropdown instead, build a recursive array, e.g. array("Homepage" => "index.html", "Topics" => array("Cars" => "cars.html", "Horses" => "horses.html"), "About me" => "about.html")
        If your navbar should contain a left and right menu, the link-array should contain exactly two arrays with the keys given as LEFT and RIGHT
        2: (string) id of the navbar
        3: (array) header of the site given as a double
        */

        $def = ["link_array" => array(), "header" => array(), "type" => "fixed-top", "atts" => array(), "node" => $this->body, "first" => true];
        extract($this->prepareForExtraction($def, func_get_args()));

        $nav_type_classes = ["fixed-top" => "fixed-top"];
        $nav_class = "navbar";
        $nav_class .= (isset($nav_type_classes[$type]) ? " navbar-" . $nav_type_classes[$type] : "");

        $atts["class"] = (isset($atts["class"]) ? $atts["class"] . " " . $nav_class : $nav_class);

        $nav = $this->add($node, "nav", "", $atts, $first);
        $container = $this->add($nav, "div", "", ["class" => "container-fluid"]);

        if(count($header) == 1){
            list($display_name, $link) = each($header);
            $header = $this->add($container, "div", "", ["class" => "navbar-header"]);
            $header_link = $this->add($header, "a", $display_name, ["class" => "navbar-brand", "href" => $link]);
        }

        if(!(isset($link_array["LEFT"]) && isset($link_array["RIGHT"]))){
            // e.g. [["text1" => "link1", "text2" => "link2"]]
            if(count($link_array) == 1 && is_array(reset($link_array))){
                $link_array = ["LEFT" => reset($link_array)];
            }
            // e.g.["text1" => "link1", "text2" => "link2"]
            else {
                $link_array = ["LEFT" => $link_array];
            }
        }

        foreach($link_array as $side => $linkList){
            $ul_atts["class"] = "nav navbar-nav";
            $ul_atts["class"] .= ($side == "RIGHT" ? " navbar-right" : "");
            $ul = $this->add($container, "ul", "", $ul_atts);

            foreach($linkList as $show_name => $link){
                if(is_array($link)){
                    $li = $this->add($ul, "li", "", ["class" => "dropdown"]);
                    $a = $this->addLink($li, "#", $show_name, ["class" => "dropdown-toggle", "data-toggle" => "dropdown"]);
                    $this->add($a, "span", "", ["class" => "caret"]);
                    $dropdown_ul = $this->add($li, "ul", "", ["class" => "dropdown-menu"]);
                    foreach($link as $dd_show_name => $dd_link){
                        $dd_li = $this->add($dropdown_ul, "li");
                        $this->addLink($dd_li, $dd_link, $dd_show_name);
                    }
                }
                else {
                    $li = $this->add($ul, "li");
                    $this->addLink($li, $link, $show_name);
                }
            }
        }

        return $nav;
    }



    /**
    * SUMMARY OF create_bootstrap_tabs
    *
    * DESCRIPTION
    *
    * @param Node $node The node to attach the tabs to.
    * @param array $tab_array The array of tabs given in the format [id_1 => tab_title_1, id_2 => tab_title_2, ...]
    *
    * @return array $node_array An array containing of the created id-prefix (to ensure uniqueness) and the tab-nodes in the format [first_id => first_node, second_id => second_node]
    */

    public function addBsTabs()
    {
        $def = ["node" => null, "tab_array" => array(), "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));

        /* we have to ensure that every tab has a unique id. this piece converts $tab_array from
        ["tab1_title", "tab2_title", "tab3_title"]
        to
        [["tab1_5fg", "tab1_title"][["tab2_5fg", "tab2_title"]][["tab3_5fg", "tab3_title"]]]*/
        $tab_id_prefix = "tab_" . rand(0,999) . "_";

        array_unshift($atts, ["nav nav-tabs"]);
        $ul = $this->add($node, "ul", "", $atts);
        $tab_container = $this->add($node, "div", "", [["tab-content"]]);
        $return_array = array();

        $i = 0;
        foreach($tab_array as $tab_id_suffix => $tab_title){
            $div_class = "tab-pane fade";
            $li_class = "";
            $id = $tab_id_prefix . $tab_id_suffix ;
            $title = $tab_title;
            if($i === 0){
                $li_class .= "active";
                $div_class .= " in active";
            }
            $li = $this->add($ul, "li", "", [[$li_class]]);
            $a_atts = ["data-toggle" => "tab", "href" => "#" . $id];
            $this->add($li, "a", $title, $a_atts);

            $return_array[$id] = $this->add($tab_container, "div", "", [[$div_class, $id]]);
            $i++;
        }
        array_unshift($return_array, $tab_id_prefix);

        return $return_array;
    }

    /**
    * [Summary].
    *
    * [Description]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    public function addBsModal()
    {

        $def = ["node" => null, "options" => array(), "atts" => array()];
        extract($this->prepareForExtraction($def, func_get_args()));
        $possible_options = ["title" => "", "id" => "login_modal", "button_texts" => ["Close", "Save changes"]];
        $options = array_merge($possible_options, $options);

        $modal = $this->add($node, "div", "", [["modal fade", $options["id"]], "tabindex" => "-1", "role" => "dialog", "aria-hidden" => "true"]);
        $dialog = $this->add($modal, "div", "", [["modal-dialog"], "role" => "document"]);
        $content = $this->add($dialog, "div", "", [["modal-content"]]);
        $header = $this->add($content, "div", "", [["modal-header"]]);
        $body = $this->add($content, "div", "", [["modal-body"]]);
        $footer = $this->add($content, "div", "", [["modal-footer"]]);

        $close_button = $this->add($header, "button", "", [["close"], "data-dismiss" => "modal", "aria-label" => "Close"]);
        $this->add($close_button, "span", "&times;", ["aria-hidden" => "true"]);
        $this->add($header, "h4", $options["title"], [["modal-title"]]);

        $this->add($footer, "button", $options["button_texts"][0], [["btn btn-secondary"], "data-dismiss" => "modal"]);
        $this->add($footer, "button", $options["button_texts"][1], [["btn btn-primary", $options["id"]. "_submit"]]);

        return ["modal" => $modal, "header" => $header, "body" => $body, "footer" => $footer];
    }

    public static function partition($array, $columns = 2, $horizontal = true)
    {
        if($horizontal){
            $partition = array();
            $i = 0;
            foreach($array as $key => $value){
                $partition[$i % $columns][$key] = $value;
                $i++;
            }
        }
        else {
            $partition = array_chunk($array, ceil(count($array)/$columns), true);
        }
        return $partition;
    }

    /**
    * [Summary].
    *
    * [Description]
    *
    * @param array $options Contains optional settings. Possible elements:
    * "ignore" => ["col1", "col2"]  Columns to not be included in the table
    * "data_types" => ["col1" => "date", "col2" => "select"]
    * "select_options" => ["col2" => ["pre_selection", "other_option_1"]
    * "header" => ["sql_column_name_1" => "display_name_1", ]
    * ]
    *
    * @return [type] [name] [description]
    */
    public function addEditableTable()
    {
        $def_options = ["ignore" => [], "data_types" => [], "select_options" => [], "table" => "undefined_table", "header" => [], "extra_columns" => null];
        $def = ["node" => null, "array" => [], "options" => $def_options, "atts" => []];
        extract($this->prepareForExtraction($def, func_get_args()));

        $ignore = $options["ignore"];
        $data_types = $this->arrayMultiFlip($options["data_types"]);
        $select_options = $options["select_options"];
        $header_row = $options["header"];

        $div = $this->add($node, "div", "", [["table-responsive"]]);

        $table = $this->add($div, "table", "", [["table editable"], "data-table" => $options["table"]]);
        $thead = $this->add($table, "thead");
        $thead_row = $this->add($thead, "tr");
        $tbody = $this->add($table, "tbody");

        /* special columns are columns prepended or appended to the table.
        They may contain buttons or extra information. Since we can not rely
        on the table having no column called "left" or "right", we'll append a random number to be sure */

        $rand = rand(0,9999);
        $special_columns["left_" . $rand] = $options["extra_columns"]["left"] ?? null;
        $special_columns["right_" . $rand] = $options["extra_columns"]["right"] ?? null;

        // if the header row is not given, we'll use the keys of the first row as labels
        if(count($header_row) == 0 && count($array) > 0){
            $first_row = reset($array);
            $header_row = array_combine(array_keys($first_row), array_keys($first_row));
        }

        foreach($special_columns as $id => $col_type){
            if(!is_null($col_type)){
                if(substr($id,0,4) == "left"){
                    $header_row = [$id => ""] + $header_row;
                }
                else{
                    $header_row = $header_row + [$id => ""];
                }
            }
        }
        // if the column names are in the ignore-array, we'll skip them
        foreach($header_row as $sql_column_name => $display_name){
            if(in_array($sql_column_name, $ignore)){
                unset($header_row[$sql_column_name]);
            }
            else {
                $this->add($thead_row, "th", $display_name);
            }
        }
        foreach($array as $row){ //cycles through all rows
            $tr = $this->add($tbody, "tr", "", ["data-id" => $row["id"]]);
            foreach($header_row as $column => $display_name){ //cycles through all columns
                $cell = $row[$column] ?? "";
                $special_column_types = $special_columns[$column] ?? false;
                if($special_column_types){
                    $data_type = "special_column";
                    $td = $this->add($tr, "td", "", [["special-column"]]);
                }
                else {
                    $data_type = $data_types[$column] ?? "text";  // default data type is text
                    $atts = ["data-column" => $column]; // these are the attributes for the field to insert into the table cell
                    if(!in_array($data_type, ["select"])){
                        $atts["value"] = $cell;
                    }
                    $td = $this->add($tr, "td");

                }

                switch($data_type){
                    case "date":
                    $atts["class"] = "date";
                    $this->addInput($td, $column, "text", $atts);
                    break;

                    case "textarea":
                    $this->addTextarea($td, $cell, $column, 4, 30, $atts);
                    break;

                    case "select":
                    $s_options = $select_options[$column] ?? array_unique(array_column($array, $column));
                    $this->addSelect($td, $column, $s_options, $atts);
                    break;

                    case "checkbox":
                    $s_options = $select_options[$column] ?? array_unique(array_column($array, $column));

                    $this->addCheckboxes($td, $column, $s_options, explode(",", $cell));
                    break;

                    case "slider":
                    if(isset($select_options[$column])){
                        $slider_options["min_max"] = $select_options[$column];
                    }
                    $slider_options["column"] = $column;
                    $slider_options["row_id"] = $row["id"];
                    $slider_options["initial_value"] = $cell;

                    $this->addSlider($td, [], $slider_options);
                    break;

                    case "showOnly":
                    $this->add($td, "span", $cell);
                    break;

                    case "radio":
                    $this->addRadio($td, $column, ["true", "false"], $cell);
                    break;

                    case "text":
                    $this->addInput($td, $column, "text", $atts);
                    break;

                    case "special_column":
                    $this->addSpecialCell($td, $special_column_types);
                    break;
                }
            }
        }


    }

    public function addSpecialCell($node, $special_column_types)
    {
        foreach($special_column_types as $c_type){
            switch($c_type)
            {
                case "checkbox":
                $this->add($node, "input", "", [["batch"], "type" => "checkbox"]);
                break;

                case "delete":
                $this->add($node, "button", "Delete me", [["delete-btn"]]);
                break;
            }
        }
    }


    /**
    * "Flatten" a 2-dimensional array using the upper keys as the new values and the lower values as keys.
    *
    * An input_array $a = ["pizza" => ["Alice", "Bob"], "soup" => ["Caesar"], "burger" => ["David", "Emily"]] will be "flattened" to one dimension for a quick
    * lookup using the recent upper-level keys as the new values and the recent lower-level values as the new keys.
    * The new array will be $a = ["Alice" => "pizza", "Bob" => "pizza", "Caesar" => "soup", "David" => "burger", "Emily" => "burger"]
    *
    * @param [Type] $[Name] [Argument description]
    *
    * @return [type] [name] [description]
    */
    private function arrayMultiFlip($input_array)
    {
        $callback = function($key, $value){
            return array_fill_keys($value, $key);
        };
        if(count($input_array) > 0){
            $return = array_map($callback, array_keys($input_array), $input_array);
            return array_reduce($return, function($prev, $curr){return array_merge($prev, $curr);}, []);
        }
        else {
            return [];
        }
    }

}