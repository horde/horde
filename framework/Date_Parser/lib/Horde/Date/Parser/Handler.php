module Chronic

  class Handler #:nodoc:
    attr_accessor :pattern, :handler_method

    def initialize(pattern, handler_method)
      @pattern = pattern
      @handler_method = handler_method
    end

    def constantize(name)
      camel = name.to_s.gsub(/(^|_)(.)/) { $2.upcase }
      ::Chronic.module_eval(camel, __FILE__, __LINE__)
    end

    def match(tokens, definitions)
      token_index = 0
      @pattern.each do |element|
        name = element.to_s
        optional = name.reverse[0..0] == '?'
        name = name.chop if optional
        if element.instance_of? Symbol
          klass = constantize(name)
          match = tokens[token_index] && !tokens[token_index].tags.select { |o| o.kind_of?(klass) }.empty?
          return false if !match && !optional
          (token_index += 1; next) if match
          next if !match && optional
        elsif element.instance_of? String
          return true if optional && token_index == tokens.size
          sub_handlers = definitions[name.intern] || raise(ChronicPain, "Invalid subset #{name} specified")
          sub_handlers.each do |sub_handler|
            return true if sub_handler.match(tokens[token_index..tokens.size], definitions)
          end
          return false
        else
          raise(ChronicPain, "Invalid match type: #{element.class}")
        end
      end
      return false if token_index != tokens.size
      return true
    end
  end

end