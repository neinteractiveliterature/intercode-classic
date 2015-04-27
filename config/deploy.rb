set :application, "intercon-j"
set :user, "deploy"
set :domain, "#{user}@vps1.interconlarp.org"
set :repository, "git@vps1.interconlarp.org:intercode1.git"
set :revision, "origin/j"
set :skip_scm, false

task :sandbox do
  set :deploy_to, "/var/www/sandbox.interactiveliterature.org"
end

task :production do
  set :deploy_to, "/var/www/#{application}"
end

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
    rm -f #{release_path}/src/$FILENAME
    ln -s #{shared_path}/local/$FILENAME #{release_path}/src/$FILENAME
  fi
done
EOF
  end
end
