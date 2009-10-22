set :application, "intercon-j"
set :user, "neiladmin"
set :domain, "#{user}@apocalypse.dreamhost.com"
set :repository, "file:///home/#{user}/svn/intercon/trunk/src"
set :deploy_to, "/home/#{user}/#{application}"

namespace :vlad do
  Rake.clear_tasks('vlad:update_symlinks')

  remote_task :update_symlinks, :roles => :app do
  end
end
