set :application, "intercon-q"
set :user, "deploy"
set :domain, "#{user}@vps1.interconlarp.org"
set :repository, "git@vps1.interconlarp.org:intercode1.git"
set :revision, "origin/master"
set :skip_scm, false

task :sandbox do
  set :deploy_to, "/var/www/sandbox.interactiveliterature.org"
end

task :production do
  set :deploy_to, "/var/www/#{application}"
end

namespace :vlad do
  Rake.clear_tasks('vlad:update_symlinks')

  task :check_deploy_to do
    begin
      Rake::RemoteTask.fetch :deploy_to
    rescue
      puts "No deployment target set.  Please run using either 'sandbox' or 'production'."
      puts "For example: rake sandbox vlad:update"
    end
  end

  %w{cleanup update rollback migrate setup}.each do |task_name|
    task task_name.to_sym => :check_deploy_to
  end

remote_task :update_symlinks, :roles => :app do
    run <<-EOF
for f in $(ls #{shared_path}/local/*)
do
  FILENAME=$(basename $f)
  if [[ $FILENAME != *~ ]]
  then
    echo "Linking in local copy of $f"
    rm -f #{release_path}/src/$FILENAME
    ln -s #{shared_path}/local/$FILENAME #{release_path}/src/$FILENAME
  fi
done
EOF
  end

  remote_task :reload_php, :roles => :app do
    run "sudo restart php5-fpm"
  end

  task :log_revision => :reload_php
end
