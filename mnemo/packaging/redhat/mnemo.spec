#
# $Horde: mnemo/packaging/redhat/mnemo.spec,v 1.10 2009/01/06 18:01:17 jan Exp $
#
# Copyright 2003-2009 The Horde Project (http://www.horde.org/)
#
# See the enclosed file LICENSE for license information (ASL). If you
# did not receive this file, see http://www.horde.org/licenses/asl.php.
#
# This is the SPEC file for the Mnemo Red Hat 7.x (RPM v4) RPMs/SRPM.
#

%define apachedir /etc/httpd
%define apacheuser apache
%define apachegroup apache
%define contentdir /var/www

Summary: The Horde contact management application.
Name: mnemo
Version: 1.1
Release: 2
License: ASL
Group: Applications/Horde
Source: ftp://ftp.horde.org/pub/mnemo/mnemo-%{version}.tar.gz
Source1: mnemo.conf
Vendor: The Horde Project
URL: http://www.horde.org/
Packager: Brent J. Nordquist <bjn@horde.org>
BuildArch: noarch
BuildRoot: %{_tmppath}/mnemo-root
Requires: php >= 4.2.1
Requires: apache >= 1.3.22
Requires: horde >= 2.1
Prereq: /usr/bin/perl

%description
Mnemo is the Horde notes and memos application. It lets users keep
free-text notes and other bits of information which doesn't fit as a
contact, a todo item, an event, etc.  It is very similar in functionality
to the Palm Memo application.

The Horde Project writes web applications in PHP and releases them under
Open Source licenses.  For more information (including help with Mnemo)
please visit http://www.horde.org/.

%prep
%setup -q -n %{name}-%{version}

%build

%install
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{apachedir}/conf
cp -p %{SOURCE1} $RPM_BUILD_ROOT%{apachedir}/conf
mkdir -p $RPM_BUILD_ROOT%{contentdir}/html/horde/mnemo
cp -pR * $RPM_BUILD_ROOT%{contentdir}/html/horde/mnemo
cd $RPM_BUILD_ROOT%{contentdir}/html/horde/mnemo/config
for d in *.dist; do
	d0=`basename $d .dist`
	if [ ! -f "$d0" ]; then
		cp -p $d $d0
	fi
done

%clean
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT

%pre

%post
perl -pi -e 's/$/ index.php/ if (/DirectoryIndex\s.*index\.html/ && !/index\.php/);' %{apachedir}/conf/httpd.conf
grep -i 'Include.*mnemo.conf$' %{apachedir}/conf/httpd.conf >/dev/null 2>&1
if [ $? -eq 0 ]; then
	perl -pi -e 's/^#+// if (/Include.*mnemo.conf$/i);' %{apachedir}/conf/httpd.conf
else
	echo "Include %{apachedir}/conf/mnemo.conf" >>%{apachedir}/conf/httpd.conf
fi
# post-install instructions:
cat <<_EOF_
You must manually configure Mnemo and create any required database tables!
See "CONFIGURING Mnemo" in %{contentdir}/html/horde/mnemo/docs/INSTALL
You must also restart Apache with "service httpd restart"!
_EOF_

%postun
if [ $1 -eq 0 ]; then
	perl -pi -e 's/^/#/ if (/^Include.*mnemo.conf$/i);' %{apachedir}/conf/httpd.conf
	cat <<_EOF2_
You must restart Apache with "service httpd restart"!
_EOF2_
fi

%files
%defattr(-,root,root)
# Apache mnemo.conf file
%config %{apachedir}/conf/mnemo.conf
# Include top level with %dir so not all files are sucked in
%dir %{contentdir}/html/horde/mnemo
# Include top-level files by hand
%{contentdir}/html/horde/mnemo/*.php
# Include these dirs so that all files _will_ get sucked in
%{contentdir}/html/horde/mnemo/graphics
%{contentdir}/html/horde/mnemo/lib
%{contentdir}/html/horde/mnemo/locale
%{contentdir}/html/horde/mnemo/po
%{contentdir}/html/horde/mnemo/scripts
%{contentdir}/html/horde/mnemo/templates
%{contentdir}/html/horde/mnemo/util
# Mark documentation files with %doc and %docdir
%doc %{contentdir}/html/horde/mnemo/LICENSE
%doc %{contentdir}/html/horde/mnemo/README
%docdir %{contentdir}/html/horde/mnemo/docs
%{contentdir}/html/horde/mnemo/docs
# Mark configuration files with %config and use secure permissions
# (note that .dist files are considered software; don't mark %config)
%attr(750,root,%{apachegroup}) %dir %{contentdir}/html/horde/mnemo/config
%defattr(640,root,%{apachegroup})
#%{contentdir}/html/horde/mnemo/config/.htaccess
%{contentdir}/html/horde/mnemo/config/*.dist
%config %{contentdir}/html/horde/mnemo/config/*.php

%changelog
* Mon Apr 28 2003 Brent J. Nordquist <bjn@horde.org> 1.1-2
- Add util subdirectory

* Sun Apr 27 2003 Brent J. Nordquist <bjn@horde.org> 1.1-1
- Updated for 1.1

* Tue Jun 25 2002 Brent J. Nordquist <bjn@horde.org>
- 1.0 release 1

