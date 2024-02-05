DOMAIN   := wp-move-attachments
PO_FILES := $(wildcard $(DOMAIN)-*.po)
MO_FILES := $(patsubst %.po,%.mo,$(PO_FILES))

POT      := $(DOMAIN).pot

MSGFMT   := msgfmt
MSGMERGE := msgmerge

.PHONY: all clean

all: $(POT) $(MO_FILES)

$(POT):
	"$(CURDIR)/../vendor/bin/wp" i18n make-pot "$(CURDIR)/../" "$(CURDIR)/../lang/$(POT)" --domain="$(DOMAIN)"

$(MO_FILES): $(PO_FILES)

%.mo: %.po
	$(MSGFMT) -o$@ $<

$(PO_FILES): %: $(POT)
	$(MSGMERGE) -U "$@" $(POT)
	touch "$@"

clean:
	-rm -f $(POT) $(MO_FILES)
