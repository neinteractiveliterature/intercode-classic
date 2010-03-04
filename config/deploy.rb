set :application, "intercon-j"
set :user, "neiladmin"
set :domain, "#{user}@apocalypse.dreamhost.com"
set :repository, "file:///home/#{user}/svn/intercon/branches/j/src"
set :deploy_to, "/home/#{user}/#{application}"

namespace :vlad do
  Rake.clear_tasks('vlad:update_symlinks')

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
