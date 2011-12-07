require 'bundler'
Bundler.setup

require 'hoe'

begin
  require "vlad"
  Vlad.load(:app => nil)
rescue Exception => e
  puts "Vlad failed to load: #{e}"
end
