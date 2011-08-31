#
# spec file for package php5-pear-<?php print $component->getName() . "\n"; ?>
#
# Copyright (c) 2011 SUSE LINUX Products GmbH, Nuernberg, Germany.
#
# All modifications and additions to the file contributed by third parties
# remain the property of their copyright owners, unless otherwise agreed
# upon. The license for this file, and modifications and additions to the
# file, is the same license as for the pristine package itself (unless the
# license for the pristine package is not an Open Source License, in which
# case the license is the MIT License). An "Open Source License" is a
# license that conforms to the Open Source Definition (Version 1.9)
# published by the Open Source Initiative.

# Please submit bugfixes or comments via http://bugs.opensuse.org/
#


%define peardir %(pear config-get php_dir 2> /dev/null || print %{_datadir}/pear)
%define xmldir  /var/lib/pear

Summary:        <?php print $component->getSummary() ."\n"; ?>

Name:           php5-pear-<?php print $component->getName() ."\n"; ?>
Version:        <?php print $component->getVersion() . "\n"; ?>
Release:        1
License:        <?php print $component->getLicense() . "\n"; ?>
Group:          Development/Libraries/PHP
Source0:        http://pear.horde.org/get/%{pear_name}-%{version}.tgz
BuildRoot:      %{_tmppath}/%{name}-%{version}-build
Url:            http://pear.horde.org/package/%{pear_name}
<?php print processDependencies($component);?>
BuildArch:      noarch
%define pear_name  <?php print $component->getName() . "\n";  ?>
%define pear_sname <?php print strtolower($component->getName()) . "\n";  ?>
# Fix for renaming (package convention)
Provides:       php5-pear-%{pear_sname} = %{version}
Provides:       php-pear-%{pear_sname} = %{version}
Provides:       pear-%{pear_sname} = %{version}
Obsoletes:      php5-pear-%{pear_sname} < %{version}
Obsoletes:      php-pear-%{pear_sname} < %{version}
Obsoletes:      pear-%{pear_sname} < %{version}

%description
<?php print $component->getDescription();?>


Lead Developers:
<?php foreach ($component->getLeads() as $devel) {
 printf("  %-20s <%s>\n", $devel['name'], $devel['email']);}  
?>

%prep
%setup -c -T
pear -v -c pearrc \
        -d php_dir=%{peardir} \
        -d doc_dir=%{_docdir}/%{name} \
        -d bin_dir=%{_bindir} \
        -d data_dir=%{peardir}/data \
        -d test_dir=%{peardir}/tests \
        -d ext_dir=%{_libdir} \
        -s

%build

%install
pear -c pearrc install --nodeps --packagingroot %{buildroot} %{SOURCE0}

# Clean up unnecessary files
rm pearrc
rm %{buildroot}/%{peardir}/.filemap
rm %{buildroot}/%{peardir}/.lock
rm -rf %{buildroot}/%{peardir}/.registry
rm -rf %{buildroot}%{peardir}/.channels
rm %{buildroot}%{peardir}/.depdb
rm %{buildroot}%{peardir}/.depdblock

# Install XML package description
mkdir -p %{buildroot}%{xmldir}
tar -xzf %{SOURCE0} package.xml
cp -p package.xml %{buildroot}%{xmldir}/%{pear_name}.xml

%clean
rm -rf %{buildroot}

%post
pear install --nodeps --soft --force --register-only %{xmldir}/%{pear_name}.xml

%postun
if [ "$1" -eq "0" ]; then
    pear uninstall --nodeps --ignore-errors --register-only pear.horde.org/%{pear_name}
fi

%files
%defattr(-,root,root)
%doc %{_docdir}/%{name}/
%{peardir}/*
%{xmldir}/%{pear_name}.xml

%changelog
