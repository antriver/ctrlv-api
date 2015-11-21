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

    config.vm.define "ctrlv", primary: true do |ctrlv|
        # Create a private network, which allows host-only access to the machine
        # using a specific IP.
        ctrlv.vm.network "private_network", ip: "192.168.33.20"

        ctrlv.vm.synced_folder ".", "/var/www/ctrlv-api",  owner: "ctrlv", group: "ctrlv",  mount_options: ["dmode=777,fmode=777"]

        ctrlv.vm.hostname = "api.ctrlv.vagrant"
        ctrlv.hostsupdater.aliases = ["img.ctrlv.vagrant"]

        ctrlv.vm.provider "virtualbox" do |vbox|
            vbox.name = "ctrlv.vagrant"
            vbox.memory = 512
            vbox.cpus = 1
        end

    end

end
