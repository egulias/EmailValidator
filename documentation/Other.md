Email length
------------
http://tools.ietf.org/html/rfc5321#section-4.1.2
  Forward-path   = Path

  Path           = "<" [ A-d-l ":" ] Mailbox ">"

http://tools.ietf.org/html/rfc5321#section-4.5.3.1.3
http://tools.ietf.org/html/rfc1035#section-2.3.4

DNS
---

http://tools.ietf.org/html/rfc5321#section-2.3.5
  Names that can
  be resolved to MX RRs or address (i.e., A or AAAA) RRs (as discussed
  in Section 5) are permitted, as are CNAME RRs whose targets can be
  resolved, in turn, to MX or address RRs.

http://tools.ietf.org/html/rfc5321#section-5.1
  The lookup first attempts to locate an MX record associated with the
  name.  If a CNAME record is found, the resulting name is processed as
  if it were the initial name. ... If an empty list of MXs is returned,
  the address is treated as if it was associated with an implicit MX
  RR, with a preference of 0, pointing to that host.

is_email() author's note: We will regard the existence of a CNAME to be
sufficient evidence of the domain's existence. For performance reasons
we will not repeat the DNS lookup for the CNAME's target, but we will
raise a warning because we didn't immediately find an MX record.

Check for TLD addresses
-----------------------
TLD addresses are specifically allowed in RFC 5321 but they are
unusual to say the least. We will allocate a separate
status to these addresses on the basis that they are more likely
to be typos than genuine addresses (unless we've already
established that the domain does have an MX record)

http://tools.ietf.org/html/rfc5321#section-2.3.5
  In the case
  of a top-level domain used by itself in an email address, a single
  string is used without any dots.  This makes the requirement,
  described in more detail below, that only fully-qualified domain
  names appear in SMTP transactions on the public Internet,
  particularly important where top-level domains are involved.

TLD format
----------
The format of TLDs has changed a number of times. The standards
used by IANA have been largely ignored by ICANN, leading to
confusion over the standards being followed. These are not defined
anywhere, except as a general component of a DNS host name (a label).
However, this could potentially lead to 123.123.123.123 being a
valid DNS name (rather than an IP address) and thereby creating
an ambiguity. The most authoritative statement on TLD formats that
the author can find is in a (rejected!) erratum to RFC 1123
submitted by John Klensin, the author of RFC 5321:

http://www.rfc-editor.org/errata_search.php?rfc=1123&eid=1353
  However, a valid host name can never have the dotted-decimal
  form #.#.#.#, since this change does not permit the highest-level
  component label to start with a digit even if it is not all-numeric.

Comments
--------
Comments at the start of the domain are deprecated in the text
Comments at the start of a subdomain are obs-domain
(http://tools.ietf.org/html/rfc5322#section-3.4.1)
