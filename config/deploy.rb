set :application, "intercon-sandbox"
set :user, "neiladmin"
set :domain, "#{user}@apocalypse.dreamhost.com"
set :repository, "file:///home/#{user}/svn/intercon/branches/pcsg/src"
set :deploy_to, "/home/#{user}/sandbox.interactiveliterature.org"

namespace :vlad do
  Rake.clear_tasks('vlad:update_symlinks')

  remote_task :setup_app, :roles => :app do
    local_dir = File.join(shared_path, 'local')
    run "umask #{umask} && mkdir -p #{local_dir}"
  end

  remote_task :update_symlinks, :roles => :app do
    run <<-EOF
for f in $(ls #{shared_path}/local/*)
do 
  FILENAME=$(basename $f)
  echo "Linking in local copy of $f"
  rm -f #{release_path}/$FILENAME
  ln -s #{shared_path}/local/$FILENAME #{release_path}/$FILENAME
done
EOF
  end
end
