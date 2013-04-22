/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

window.CoreSphereConsole = (function (window) {
    "use strict";

    if (typeof window.jQuery === "undefined") {
        window.alert('jQuery has not been loaded');
        return;
    }

    var $ = window.jQuery,

        default_options = {
            'filters' : {
                'clear' : function () {
                    this.log.html('');

                    return '';
                }
            },

            'active_suggestion_class' : 'active',

            'command_splitter' : '|'
        },

        helpers = {

            htmlEscape : function (input) {
                return input
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            },

            isInteger : function(n) {
              return n===+n && n===(n|0);
            },

            regexpEscape : (function () {
                var regexp = new RegExp(
                    '(\\' + [
                        '.', '*', '+', '?', '|',
                        '^', '$',
                        '(', ')', '[', ']', '{', '}', '\\'
                    ].join('|\\') + ')',
                    'g'
                );

                return function (input) {
                    return input.replace(regexp, '\\$1');
                };
            }()),

            focusInput : function (el) {
                var range, sel, textRange;
                el.focus();
                if (typeof window.getSelection !== "undefined"
                        && typeof window.document.createRange !== "undefined") {
                    range = window.document.createRange();
                    sel = window.getSelection();
                    range.selectNodeContents(el);
                    range.collapse(false);
                    sel.removeAllRanges();
                    sel.addRange(range);
                } else if (typeof window.document.body.createTextRange !== "undefined") {
                    textRange = window.document.body.createTextRange();
                    textRange.moveToElementText(el);
                    textRange.collapse(false);
                    textRange.select();
                }
            }

        },

        keys = {
            'tab' : 9,
            'enter' : 13,
            'escape' : 27,
            'left' : 37,
            'up' : 38,
            'right' : 39,
            'down': 40
        },

        ConsoleBackend = function() {

        },

        Console = function (base_element, options) {
            this.options = $.extend(default_options, options);

            this.base             = base_element;
            this.input            = base_element.find('.console_input');
            this.input_background = base_element.find('.console_input_background');
            this.log_container    = base_element.find('.console_log_container');
            this.log              = base_element.find('.console_log');
            this.suggestion_box   = base_element.find('.console_suggestions');
            this.active_suggestion = null;

            this.initHistory();
            this.bindEvents();
            this.unlock();
            this.focus();
            this.welcome();
        };

    ConsoleBackend.prototype.knownCommands = [];
    ConsoleBackend.prototype.history = [];
    ConsoleBackend.prototype.historyPosition = 0;
    ConsoleBackend.prototype.currentInput = [];
    ConsoleBackend.prototype.currentSuggestions = [];
    ConsoleBackend.prototype.focusedSuggestion = 0;

    ConsoleBackend.prototype.init = function() {
        var that = this;

        that.knownCommands = [];
        that.history = [];
        that.historyPosition = 0;
        that.currentInput = [];
        that.currentSuggestions = [];
        that.focusedSuggestion = 0;
    };

    ConsoleBackend.prototype.focusSuggestionAt = function(idx) {
        var that = this;

        if(!isInteger(idx)) {
            throw "Index to be focused must be an integer.";
        }
        if(idx<0 || idx+1 > that.currentSuggestions.length) {
            throw "Suggestion focus is out of range.";
        }

        that.focusedSuggestion = idx;
    };

    ConsoleBackend.prototype.focusNextSuggestion = function() {
        var that = this
          , mod = that.currentSuggestions.length;

        that.focusedSuggestion = (that.focusedSuggestion+1)%mod;
    };

    ConsoleBackend.prototype.focusPrevSuggestion = function() {
        var that = this
          , mod = that.currentSuggestions.length;

        that.focusedSuggestion = (that.focusedSuggestion+mod-1)%mod;
    };

    ConsoleBackend.prototype.pushHistory = function(value) {
        var that = this;

        that.history.push(value);
    };

    ConsoleBackend.prototype.clearHistory = function() {
        var that = this;

        that.history.length = 0;
    };

    ConsoleBackend.prototype.setRawInput = function() {
        var that = this;
    };

    ConsoleBackend.prototype.setInputList = function() {
        var that = this;
    };

    Console.prototype.focus = function () {
        helpers.focusInput(this.input[0]);
    };

    Console.prototype.initHistory = function () {
        if (!window.localStorage.coresphere_console_history) {
            window.localStorage.coresphere_console_history = '[]';
        }

        this.history = JSON.parse(window.localStorage.coresphere_console_history).slice(-20);
        this.history_position = this.history.length;
    };

    Console.prototype.pushHistory = function (val) {
        if (val.length && this.history[this.history.length - 1] !== val) {
            this.history.push(val);
            window.localStorage.coresphere_console_history = JSON.stringify(this.history);
        }

        this.history_position = this.history.length;
    };

    Console.prototype.clearHistory = function () {
        window.localStorage.removeKey('coresphere_console_history');
    };

    Console.prototype.bindEvents = function () {
        var enable_suggestions = true,
            this_console = this;

        this.base

            .on('click.coresphere_console', '.console_command', function (e) {
                enable_suggestions = true;
                this_console.setValue($(this).text()).trigger('keyup');
                this_console.focus();
            })

            .on('click.coresphere_console', '.console_suggestions li', function (e) {
                e.stopPropagation();
                var $this = $(this);
                this_console.setCurrentCommand($this.text());
                this_console.clearSuggestions();
                this_console.focus();
            })

            .on('click.coresphere_console', '.console_log_input', function (e) {
                $(this).next('.console_log_output').stop().slideToggle(100);
            })

            .on('keydown.coresphere_console', '.console_input', function (e) {
                var val,
                    command,
                    filter,
                    current_suggestions,
                    active_suggestion,
                    next,
                    scrollToEl;

                if (this_console.isLocked()) {
                    return;
                }

                val = this_console.getValue();

                if (e.which === keys.tab) {

                    e.preventDefault();

                    if (this_console.active_suggestion) {
                        this_console.setCurrentCommand(this_console.active_suggestion);
                    }

                    this_console.focus();

                } else if (e.which === keys.enter && !e.shiftKey) {

                    if (this_console.active_suggestion) {
                        if (val !== this_console.active_suggestion) {
                            this_console.setCurrentCommand(val = this_console.active_suggestion);
                            this_console.clearSuggestions();
                            this_console.focus();

                            e.preventDefault();
                            return;
                        }
                    }

                    command = val;

                    this_console.setValue('');

                    this_console.pushHistory(val);

                    if (command.substr(0, 1) === '.') {
                        filter = command.substr(1);

                        if (this_console.options.filters[filter]) {
                            command = this_console.options.filters[filter].call(this_console);
                        }
                    }

                    if (command.length) {
                        this_console.lock();

                        this_console.log.find('li:not(.console_loading) .console_log_output').last().hide();

                        this_console.log.append(this_console.options.templates.loading
                            .replace('%command%', helpers.htmlEscape(val))
                            .replace('%message%', this_console.options.lang.loading)
                        );

                        this_console.sendCommands(command.split(this_console.options.command_splitter));
                    } else {
                        this_console.focus();
                    }

                    e.preventDefault();

                } else if (e.which === keys.up || e.which === keys.down) {
                    e.preventDefault();

                    current_suggestions = this_console.suggestion_box.find('li');
                    active_suggestion = current_suggestions.filter('.' + this_console.options.active_suggestion_class);

                    if (e.which === keys.up) {
                        if (current_suggestions.size()) {
                            next = active_suggestion.size() ? active_suggestion.removeClass(this_console.options.active_suggestion_class).prev() : current_suggestions.last();
                            next = next.size() ? next : current_suggestions.last();
                            this_console.setActiveSuggestion(next.addClass(this_console.options.active_suggestion_class).text());
                            scrollToEl = next[0];
                        } else {
                            this_console.history_position -= 1;
                            if (this_console.history_position < 0) {
                                this_console.history_position = 0;
                            } else {
                                this_console.setValue(this_console.history[this_console.history_position]);
                            }
                        }
                    } else {
                        // DOWN

                        if (current_suggestions.size()) {
                            next = active_suggestion.size() ? active_suggestion.removeClass(this_console.options.active_suggestion_class).next() : current_suggestions.first();
                            next = next.size() ? next : current_suggestions.first();

                            this_console.setActiveSuggestion(next.addClass(this_console.options.active_suggestion_class).text());
                            scrollToEl = next[0];
                        } else {
                            this_console.history_position += 1;
                            if (this_console.history_position >= this_console.history.length) {
                                this_console.history_position = this_console.history.length;
                                this_console.setValue('');
                            } else {
                                this_console.setValue(this_console.history[this_console.history_position]);
                            }
                        }

                    }

                    if(scrollToEl) {
                        var rects = scrollToEl.getClientRects()[0];
                        if(document.elementFromPoint(Math.ceil(rects.left),Math.ceil(rects.top)) !== scrollToEl
                        || document.elementFromPoint(Math.floor(rects.right),Math.floor(rects.bottom)) !== scrollToEl) {
                            scrollToEl.scrollIntoView(false);
                        }
                    }
                    this_console.focus();
                    enable_suggestions = false;


                } else if (e.which === keys.escape) {

                    if (this_console.suggestion_box.find('li').size()) {
                        this_console.suggestion_box.text('');
                    } else {
                        this_console.popValue();
                    }
                    enable_suggestions = false;
                    this_console.focus();
                }

                if ((e.which < keys.left || e.which > keys.down) && e.which !== keys.escape) {
                    enable_suggestions = true;
                }
            })

            .on('keyup.coresphere_console', '.console_input', function (e) {

                if (enable_suggestions) {
                    var val = this_console.getValue(),
                        best_suggestions = [],
                        other_suggestions = [],
                        suggestions,
                        any = 0,
                        htmlCode,
                        index,
                        j,
                        tpl,
                        suggestion,
                        commands = val.split(this_console.options.command_splitter),
                        currentCommand = commands[commands.length-1];


                    if (currentCommand.length) {
                        for (index = 0, j = this_console.options.commands.length; index < j; index++) {
                            if (new RegExp('^' + helpers.regexpEscape(currentCommand)).test(this_console.options.commands[index])) {
                                best_suggestions.push(this_console.options.commands[index]);
                                any += 1;
                            } else if (new RegExp(helpers.regexpEscape(currentCommand)).test(this_console.options.commands[index])) {
                                other_suggestions.push(this_console.options.commands[index]);
                                any += 1;
                            }
                            if (this_console.options.commands[index] === currentCommand) {
                                any -= 1;
                            }
                        }

                        suggestions = best_suggestions.concat(other_suggestions);
                    }

                    if (any) {
                        if (!this_console.active_suggestion || suggestions.indexOf(this_console.active_suggestion) < 0) {
                            this_console.setActiveSuggestion(suggestions[0]);
                        } else {
                            this_console.setActiveSuggestion(this_console.active_suggestion);
                        }

                        enable_suggestions = false;
                        htmlCode = '';

                        for (index = 0, j = suggestions.length; index < j; index++) {
                            suggestion = suggestions[index].replace(currentCommand, this_console.options.templates.highlight.replace('%word%', currentCommand));
                            if(suggestions[index] === this_console.active_suggestion) {
                                tpl = this_console.options.templates.suggestion_item_active;
                                tpl = tpl.replace('%state%', this_console.options.active_suggestion_class);
                            } else {
                                tpl = this_console.options.templates.suggestion_item;
                            }
                            htmlCode += tpl.replace("%suggestion%", suggestion);
                        }

                        htmlCode = this_console.options.templates.suggestion_list
                            .replace('%head%', this_console.options.lang.suggestion_head)
                            .replace('%suggestions%', htmlCode)
                        ;

                        this_console.suggestion_box.html(htmlCode);
                    } else {
                        this_console.setActiveSuggestion(null);

                        this_console.suggestion_box.text('');
                    }
                }

            })

            .on('focus.coresphere_console', '.console_input', function (e) {
                this_console.suggestion_box.show();
            });

        $(window.document)

            .on('mousedown.coresphere_console', function (e) {
                var $target = $(e.target);
                this_console.focus();
                if ($target.is('.console_input')
                        || $target.closest('.console_suggestions').size()
                        ) {
                    return;
                }
                this_console.suggestion_box.hide();
            })

            .on('focus.coresphere_console', function (e) {
                this_console.focus();
            });
    };

    Console.prototype.unbindEvents = function () {
        this.base.off('.coresphere_console');
        $(window.document).off('.coresphere_console');
    };


    Console.prototype.lock = function () {
        this.locked = true;
    };

    Console.prototype.unlock = function () {
        this.locked = false;
    };

    Console.prototype.isLocked = function () {
        return this.locked === true;
    };

    Console.prototype.getValue = function () {
        return this.input.text();
    };

    Console.prototype.setValue = function (val) {
        this.setActiveSuggestion(null);
        return this.input.text(val);
    };

    Console.prototype.popValue = function () {
        var commands = this.getCommands();
        commands.pop();

        return this.setValue( commands.join(this.options.command_splitter) );
    };

    Console.prototype.setCurrentCommand = function (val) {
        var commands = this.getCommands(),
            i = this.getCurrentCommand();

        commands[i] = val;
        this.setActiveSuggestion(null);
        return this.input.text(commands.join(this.options.command_splitter));
    };

    Console.prototype.getCommands = function() {
        var oldValue = this.getValue(),
            commands = oldValue.split(this.options.command_splitter);

        return commands;
    };

    Console.prototype.getCurrentCommand = function() {
        return this.getCommands().length - 1;
    };

    Console.prototype.getActiveSuggestion = function () {
        return this.active_suggestion;
    };

    Console.prototype.setActiveSuggestion = function(suggestion) {
        this.active_suggestion = suggestion;

        if(this.getCommands().length < 2) {
            this.input_background.text(this.active_suggestion || '');

            if(suggestion) {
                this.input.attr('data-before', suggestion.substr(0, suggestion.indexOf(this.input.text())));
            } else {
                this.input.attr('data-before', '');
            }
        } else {
            this.input.attr('data-before', '');
            this.input_background.text('');
        }
    };

    Console.prototype.clearSuggestions = function () {
        this.setActiveSuggestion(null);
        return this.suggestion_box.text('');
    };

    Console.prototype.welcome = function () {
        this.log.append('<li>' + this.options.lang.welcome_message + '</li>');
    };

    Console.prototype.commandComplete = function (response) {
        var answer, htmlCode, cmd,
            results = response.results,
            tplCmd = this.options.templates.command,
            tplEnv = this.options.templates.environment;

        for (var i = 0, len = results.length; i < len; i++) {
            cmd = results[i];
            answer = cmd.output.replace(/^\s+|\s+$/g, "");
            htmlCode = tplCmd
                .replace("%command%", helpers.htmlEscape(cmd.command))
                .replace("%status%", 0 == cmd.error_code ? 'ok' : 'error')
                .replace("%environment%", cmd.environment !== this.options.environment
                    ? tplEnv.replace("%label%", this.options.lang.environment).replace("%environment%", cmd.environment)
                    : ''
                )
                .replace("%output%", answer.length ? answer : this.options.lang.empty_response)
            ;

            this.log.append(htmlCode);
        }
    };

    Console.prototype.commandError = function (xhr, msg, error) {
        this.log.append(
            this.options.templates.error
                .replace("%message%", msg)
                .replace("%error%", error)
                .replace("%command%", '')
        );
    };

    Console.prototype.commandAfter = function () {
        this.log.find('.console_loading').remove();
        this.unlock();
        this.log_container.scrollTop(this.log.outerHeight());
        this.focus();
    };

    Console.prototype.sendCommands = function (commands) {

        var this_console = this;

        return $.ajax({
            url: this.options.post_path,
            type: "POST",
            data: ({"commands" : commands}),
            dataType: "json"
            })

            .done(this.commandComplete.bind(this))

            .fail(this.commandError.bind(this))

            .always(this.commandAfter.bind(this));
    };

    return Console;

}(window));
