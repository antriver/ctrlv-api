# -*- mode: ruby -*-
# vi: set ft=ruby :
Vagrant.configure("2") do |config|

    config.vm.box = "ubuntu/trusty64"

    config.vm.provision "puppet" do |puppet|
        puppet.manifests_path = "~/Dropbox/Websites/Puppet"
        puppet.manifest_file = "manifest.pp"
        puppet.module_path = "~/Dropbox/Websites/Puppet/modules"
        puppet.options = "-v --environment vagrant"
    end

    config.vm.define "ctrlvapi", primary: true do |ctrlvapi|

        ctrlvapi.vm.network "public_network", ip: "192.168.0.201", bridge: "en0: Wi-Fi (AirPort)"

        # Create a private network, which allows host-only access to the machine
        # using a specific IP.
        #ctrlvapi.vm.network "private_network", ip: "192.168.33.20"

        ctrlvapi.vm.synced_folder ".", "/var/www/ctrlv-api",  owner: "www-data", group: "www-data",  mount_options: ["dmode=777,fmode=777"]
        ctrlvapi.vm.synced_folder "../ctrlv-frontend", "/var/www/ctrlv-frontend",  owner: "www-data", group: "www-data",  mount_options: ["dmode=777,fmode=777"]

        ctrlvapi.vm.hostname = "api.vagrant.ctrlv.in"
        ctrlvapi.hostsupdater.aliases = ["img.vagrant.ctrlv.in", "vagrant.ctrlv.in", "assets.vagrant.ctrlv.in"]

        ctrlvapi.vm.provider "virtualbox" do |vbox|
            vbox.name = "api.ctrlv.vagrant"
            vbox.memory = 512
            vbox.cpus = 1
        end

    end

end
