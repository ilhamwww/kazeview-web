function readU32(b, o) { return (b[o]<<24|b[o+1]<<16|b[o+2]<<8|b[o+3])>>>0; }
function readU64(b, o) { return readU32(b,o)*0x100000000+readU32(b,o+4); }
function readTag(b, o) { return String.fromCharCode(b[o],b[o+1],b[o+2],b[o+3]); }
function writeTag(b, o, t) { for(let i=0;i<4;i++) b[o+i]=t.charCodeAt(i); }

function iterBoxes(buf, s, e) {
  const boxes=[]; let off=s;
  while(off+8<=e){ const sz=readU32(buf,off); if(sz<8||off+sz>e)break;
    boxes.push({offset:off,size:sz,tag:readTag(buf,off+4),bodyOff:off+8}); off+=sz; }
  return boxes;
}
function findBox(buf,s,e,tag){ for(const b of iterBoxes(buf,s,e)) if(b.tag===tag) return b; return null; }
function walkPath(buf,s,e,...tags){
  let cs=s,ce=e,found=null;
  for(const tag of tags){ found=null;
    for(const b of iterBoxes(buf,cs,ce)){ if(b.tag===tag){found=b;cs=b.bodyOff;ce=b.offset+b.size;break;} }
    if(!found) return null; }
  return found;
}

function parseSampleTable(buf, ss, se) {
  const find=t=>findBox(buf,ss,se,t);
  const stsz=find('stsz'),stsc=find('stsc'),stco=find('stco'),co64=find('co64'),senc=find('senc');
  if(!stsz||!stsc||(!stco&&!co64)) return null;
  const defSz=readU32(buf,stsz.bodyOff+4), n=readU32(buf,stsz.bodyOff+8);
  const sizes=new Array(n);
  if(defSz===0){for(let i=0;i<n;i++) sizes[i]=readU32(buf,stsz.bodyOff+12+i*4);}else sizes.fill(defSz);
  let chunkOffs;
  if(stco){const c=readU32(buf,stco.bodyOff+4);chunkOffs=new Array(c);for(let i=0;i<c;i++) chunkOffs[i]=readU32(buf,stco.bodyOff+8+i*4);}
  else{const c=readU32(buf,co64.bodyOff+4);chunkOffs=new Array(c);for(let i=0;i<c;i++) chunkOffs[i]=readU64(buf,co64.bodyOff+8+i*8);}
  const ne=readU32(buf,stsc.bodyOff+4),entries=[];
  for(let i=0;i<ne;i++) entries.push({firstChunk:readU32(buf,stsc.bodyOff+8+i*12),spc:readU32(buf,stsc.bodyOff+12+i*12)});
  const sampleOffs=[]; let si=0;
  for(let ci=0;ci<chunkOffs.length&&si<n;ci++){
    let spc=1; for(const e of entries){if(ci+1>=e.firstChunk)spc=e.spc;else break;}
    let cur=chunkOffs[ci]; for(let k=0;k<spc&&si<n;k++){sampleOffs.push(cur);cur+=sizes[si];si++;} }
  let ivs=[];
  if(senc){
    const flags=(buf[senc.bodyOff+1]<<16)|(buf[senc.bodyOff+2]<<8)|buf[senc.bodyOff+3];
    const cnt=readU32(buf,senc.bodyOff+4); let off=senc.bodyOff+8;
    for(let i=0;i<cnt;i++){
      ivs.push(buf.slice(off,off+8)); off+=8;
      if(flags&0x02){off+=2+((buf[off]<<8)|buf[off+1])*6;} } }
  return {sampleOffs,sizes,ivs};
}

function parseAllTracks(buf, moov) {
  const tracks=[];
  for(const trak of iterBoxes(buf,moov.bodyOff,moov.offset+moov.size)){
    if(trak.tag!=='trak') continue;
    const mdia=walkPath(buf,trak.bodyOff,trak.offset+trak.size,'mdia'); if(!mdia) continue;
    const hdlr=walkPath(buf,mdia.bodyOff,mdia.offset+mdia.size,'hdlr'); if(!hdlr) continue;
    const h=readTag(buf,hdlr.offset+16); if(h!=='vide'&&h!=='soun') continue;
    const stbl=walkPath(buf,mdia.bodyOff,mdia.offset+mdia.size,'minf','stbl'); if(!stbl) continue;
    const t=parseSampleTable(buf,stbl.bodyOff,stbl.offset+stbl.size);
    if(t&&t.ivs.length>0) tracks.push(t);
  }
  return tracks;
}

function neutralizeDRM(buf, ms, me) {
  const rename={'encv':'hvc1','enca':'mp4a','sinf':'free','schm':'free','schi':'free',
    'senc':'free','saio':'free','saiz':'free','tenc':'free','frma':'free','pssh':'free'};
  for(let i=ms+4;i+4<=me;i++){
    const tag=readTag(buf,i);
    if(tag in rename){const sz=readU32(buf,i-4);if(sz>=8&&sz<=me-i+4)writeTag(buf,i,rename[tag]);}
  }
  let off=0;
  while(off+8<=buf.length){const sz=readU32(buf,off);if(sz<8||off+sz>buf.length)break;
    if(readTag(buf,off+4)==='pssh')writeTag(buf,off+4,'free'); off+=sz;}
}

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  if (url.pathname === '/sw-play') {
    event.respondWith(handleStreamDecrypt(url));
  }
});

async function handleStreamDecrypt(url) {
  const cdnURL = url.searchParams.get('url');
  const keyHex = url.searchParams.get('key');
  if (!cdnURL || !keyHex) return new Response('Missing params', {status:400});

  const keyBytes = new Uint8Array(keyHex.match(/.{2}/g).map(h=>parseInt(h,16)));
  const cryptoKey = await crypto.subtle.importKey('raw', keyBytes, {name:'AES-CTR'}, false, ['decrypt']);

  const resp = await fetch(cdnURL);
  if (!resp.ok) return new Response('CDN error '+resp.status, {status:502});
  const total = parseInt(resp.headers.get('Content-Length')||'0');

  const reader = resp.body.getReader();

  let buf = total > 0 ? new Uint8Array(total) : new Uint8Array(20*1024*1024);
  let received = 0;
  let moovParsed = false;
  let moovEnd = 0;
  let samples = [];
  let outputPos = 0;
  let sampleIdx = 0;

  const stream = new ReadableStream({
    async pull(controller) {
      while (true) {
        const {done, value} = await reader.read();
        if (done) {
          if (moovParsed) {
            await flushSamples(controller, received, cryptoKey);
            if (outputPos < received) {
              controller.enqueue(buf.slice(outputPos, received));
            }
          } else {
            controller.enqueue(buf.slice(0, received));
          }
          controller.close();
          return;
        }

        buf.set(value, received);
        received += value.length;

        if (!moovParsed && received > 512) {
          const moov = walkPath(buf, 0, received, 'moov');
          if (moov && moov.offset + moov.size <= received) {
            moovParsed = true;
            moovEnd = moov.offset + moov.size;

            const tracks = parseAllTracks(buf, moov);
            for (const t of tracks) {
              const cnt = Math.min(t.sampleOffs.length, t.ivs.length);
              for (let i = 0; i < cnt; i++) {
                samples.push({offset:t.sampleOffs[i], size:t.sizes[i], iv:t.ivs[i]});
              }
            }
            samples.sort((a,b) => a.offset - b.offset);

            neutralizeDRM(buf, moov.offset, moovEnd);

            controller.enqueue(buf.slice(0, moovEnd));
            outputPos = moovEnd;
          }
        }

        if (moovParsed) {
          const flushed = await flushSamples(controller, received, cryptoKey);
          if (flushed > 0) return;
        }
      }
    }
  });

  async function flushSamples(controller, avail, key) {
    let flushed = 0;

    const batch = [];
    let batchStart = sampleIdx;
    while (batchStart + batch.length < samples.length) {
      const s = samples[batchStart + batch.length];
      if (s.offset + s.size > avail) break;
      batch.push(s);
      if (batch.length >= 200) break;
    }
    if (batch.length === 0) return 0;

    const decResults = await Promise.all(batch.map(s => {
      const ctr = new Uint8Array(16);
      ctr.set(s.iv, 0);
      return crypto.subtle.decrypt(
        {name:'AES-CTR', counter:ctr, length:64}, key,
        buf.buffer.slice(buf.byteOffset + s.offset, buf.byteOffset + s.offset + s.size)
      );
    }));

    for (let i = 0; i < batch.length; i++) {
      const s = batch[i];
      if (outputPos < s.offset) {
        controller.enqueue(buf.slice(outputPos, s.offset));
        flushed += s.offset - outputPos;
      }
      controller.enqueue(new Uint8Array(decResults[i]));
      flushed += s.size;
      outputPos = s.offset + s.size;
    }
    sampleIdx += batch.length;

    if (sampleIdx < samples.length) {
      const nextStart = samples[sampleIdx].offset;
      if (outputPos < nextStart && outputPos < avail) {
        const end = Math.min(nextStart, avail);
        controller.enqueue(buf.slice(outputPos, end));
        flushed += end - outputPos;
        outputPos = end;
      }
    }

    return flushed;
  }

  return new Response(stream, {
    status: 200,
    headers: Object.assign(
      { 'Content-Type': 'video/mp4', 'Accept-Ranges': 'none' },
      total > 0 ? { 'Content-Length': total.toString() } : {}
    )
  });
}
