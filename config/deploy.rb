set :application, "intercon-l"
set :user, "neiladmin"
set :domain, "#{user}@transit.dreamhost.com"
set :repository, "file:///home/#{user}/svn/intercon/branches/L/src"
set :skip_scm, false

task :sandbox do
  set :deploy_to, "/home/#{user}/sandbox.interactiveliterature.org"
end

task :production do
  set :deploy_to, "/home/#{user}/#{application}"
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
    rm -f #{release_path}/$FILENAME
    ln -s #{shared_path}/local/$FILENAME #{release_path}/$FILENAME
  fi
done
EOF
  end
end
