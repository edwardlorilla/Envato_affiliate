(function ($) {
    "use strict";
    
    var Intervals = function (options) {
        this.init('intervals', options, Intervals.defaults);
    };

    //inherit from Abstract input
    $.fn.editableutils.inherit(Intervals, $.fn.editabletypes.abstractinput);

    $.extend(Intervals.prototype, {
        /**
        Renders input from tpl

        @method render() 
        **/        
        render: function() {
           this.$input = this.$tpl.find('input');
        },
        
        /**
        Default method to show value in element. Can be overwritten by display option.
        
        @method value2html(value, element) 
        **/
        value2html: function(value, element) {
            if(!value) {
                $(element).empty();
                return; 
            }
	    var s = value.split('-');
            var html = $('<div>').text(s[0]).html() + ' - ' + $('<div>').text(s[1]).html();
            $(element).html(html); 
        },
        
        /**
        Gets value from element's html
        
        @method html2value(html) 
        **/        
        html2value: function(html) {        
          return null;  
        },
      
       /**
        Converts value to string. 
        It is used in internal comparing (not for sending to server).
        
        @method value2str(value)  
       **/
       value2str: function(value) {
           var str = '';
           if(value) {
               for(var k in value) {
                   str = str + k + ':' + value[k] + ';';  
               }
           }
           return str;
       }, 
       
       /*
        Converts string to value. Used for reading value from 'data-value' attribute.
        
        @method str2value(str)  
       */
       str2value: function(str) {
           /*
           this is mainly for parsing value defined in data-value attribute. 
           If you will always set value by javascript, no need to overwrite it
           */
           return str;
       },                
       
       /**
        Sets value of input.
        
        @method value2input(value) 
        @param {mixed} value
       **/         
       value2input: function(value) {
           if(!value) {
             return;
           }
	   var s = value.split('-');
           this.$input.filter('[name="min"]').val(s[0]);
           this.$input.filter('[name="max"]').val(s[1]);
       },       
       
       /**
        Returns value of input.
        
        @method input2value() 
       **/          
       input2value: function() {
	   var min = +this.$input.filter('[name="min"]').val();
	   var max = +this.$input.filter('[name="max"]').val();
	   if (min || max) {
	       return max > min ? min +'-' + max : max + '-' + min;
	   }
	   return '';
       },        
       
        /**
        Activates input: sets focus on the first field.
        
        @method activate() 
       **/        
       activate: function() {
            this.$input.filter('[name="min"]').focus();
       },  
       
       /**
        Attaches handler to submit form in case of 'showbuttons=false' mode
        
        @method autosubmit() 
       **/       
       autosubmit: function() {
           this.$input.keydown(function (e) {
                if (e.which === 13) {
                    $(this).closest('form').submit();
                }
           });
       }       
    });

    Intervals.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        tpl: '<div class="editable-intervals">'
		+ '<input type="text" placeholder="Min" class="input-mini" name="min" size="4">'
		+ '<input type="text" placeholder="Max" class="input-mini" name="max" size="4">'
	    + '</div>',
             
        inputclass: ''
    });

    $.fn.editabletypes.intervals = Intervals;

}(window.jQuery));