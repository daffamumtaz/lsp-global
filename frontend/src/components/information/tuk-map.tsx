"use client";

import { useEffect, useRef, useState } from "react";
import L from "leaflet";
import "leaflet.markercluster";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster/dist/MarkerCluster.css";
import { hasCoordinates, type Tuk } from "@/lib/tuks";
import { registrationHref } from "@/lib/schemes";
import styles from "./tuk-map.module.css";

const indonesia: L.LatLngBoundsExpression = [[-10, 98], [5, 140]];

export default function TukMap({ entries, onSelect }: { entries: Tuk[]; onSelect: (ids: string[]) => void }) {
  const container = useRef<HTMLDivElement>(null);
  const mapRef = useRef<L.Map | null>(null);
  const groupRef = useRef<L.MarkerClusterGroup | null>(null);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    const element = container.current;
    if (!element) return;
    let disposed = false;
    const map = L.map(element, { scrollWheelZoom: false, zoomControl: false, maxZoom: 19, zoomSnap: 0.25 });
    mapRef.current = map;
    L.control.zoom({ position: "bottomleft" }).addTo(map);
    map.fitBounds(indonesia, { padding: [12, 12] });
    L.tileLayer(process.env.NEXT_PUBLIC_MAP_TILE_URL || "https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: process.env.NEXT_PUBLIC_MAP_ATTRIBUTION || '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    }).on("tileerror", () => setFailed(true)).addTo(map);
    const group = L.markerClusterGroup({
      zoomToBoundsOnClick: false, spiderfyOnMaxZoom: false, showCoverageOnHover: false,
      iconCreateFunction(cluster) {
        const count = cluster.getChildCount();
        cluster.options.title = `${count} TUK dalam kelompok. Pilih untuk melihat daftar.`;
        return L.divIcon({ html: `<span role="img" aria-label="${count} TUK dalam kelompok. Pilih untuk melihat daftar.">${count}</span>`, className: styles.cluster, iconSize: [44, 44] });
      },
    }).addTo(map);
    groupRef.current = group;
    map.on("layeradd", event => {
      if (event.layer instanceof L.Marker && !(event.layer instanceof L.MarkerCluster)) {
        const element = event.layer.getElement();
        if (element) element.setAttribute("aria-label", event.layer.options.title || "Pilih TUK");
      }
    });
    const resize = new ResizeObserver(() => {
      if (disposed || !element.isConnected || mapRef.current !== map) return;
      const maxWidth = Math.min(260, Math.max(120, element.clientWidth - 112));
      map.invalidateSize();
      group.eachLayer(layer => {
        if (disposed || mapRef.current !== map) return;
        if (!(layer instanceof L.Marker)) return;
        const popup = layer.getPopup();
        if (!popup) return;
        popup.options.maxWidth = maxWidth;
        if (popup.isOpen()) popup.update();
      });
    });
    resize.observe(element);
    return () => { disposed = true; resize.disconnect(); mapRef.current = null; groupRef.current = null; map.remove(); };
  }, []);

  useEffect(() => {
    const group = groupRef.current;
    const element = container.current;
    if (!group || !element) return;
    const ids = new Map<L.Marker, string>();
    group.clearLayers();
    for (const tuk of entries.filter(hasCoordinates)) {
      const marker = L.marker([tuk.latitude, tuk.longitude], {
        title: `Pilih ${tuk.name} — ${[tuk.city, tuk.province].filter(Boolean).join(", ") || "Lokasi TUK"}`,
        icon: L.divIcon({ html: '<span aria-hidden="true">●</span>', className: styles.marker, iconSize: [44, 44] }),
      }).on("click", () => onSelect([tuk.id]));
      const details = document.createElement("div");
      details.className = styles.details;
      const name = document.createElement("strong");
      name.textContent = tuk.name;
      const address = document.createElement("p");
      address.textContent = tuk.address || [tuk.city, tuk.province].filter(Boolean).join(", ");
      details.append(name, address);
      const mapsHref = registrationHref(tuk.maps_url);
      if (mapsHref) {
        const link = document.createElement("a");
        link.href = mapsHref;
        link.target = "_blank";
        link.rel = "noopener noreferrer";
        link.textContent = "Buka di Google Maps ↗";
        link.setAttribute("aria-label", `Buka ${tuk.name} di Google Maps (tab baru)`);
        details.append(link);
      }
      marker.bindPopup(details, { maxWidth: Math.min(260, Math.max(120, element.clientWidth - 112)), minWidth: 120, autoPanPadding: [20, 20] });
      ids.set(marker, tuk.id);
      group.addLayer(marker);
    }
    const selectCluster = (event: L.LeafletEvent) => {
      const cluster = (event as unknown as { layer: L.MarkerCluster }).layer;
      onSelect(cluster.getAllChildMarkers().map(marker => ids.get(marker)!).filter(Boolean));
    };
    group.on("clusterclick", selectCluster);
    return () => { group.off("clusterclick", selectCluster); group.clearLayers(); };
  }, [entries, onSelect]);

  return <div className={styles.wrapper}>
    <div className={styles.controls}>
      <p>Pilih titik untuk melihat alamat dan Google Maps. Pilih angka untuk melihat daftar TUK dalam kelompok.</p>
      <button type="button" onClick={() => mapRef.current?.fitBounds(indonesia, { padding: [12, 12] })}>Kembali ke Indonesia</button>
    </div>
    <div ref={container} className={styles.map} role="region" aria-label="Peta lokasi TUK Indonesia" onKeyDown={event => {
      if (event.key === "Escape") mapRef.current?.closePopup();
      if ((event.key === " " || event.key === "Enter") && event.target instanceof HTMLElement && event.target.classList.contains("leaflet-marker-icon")) {
        event.preventDefault(); event.target.click();
      }
    }} />
    {failed && <p className={styles.message} role="status">Latar peta tidak dapat dimuat. Titik lokasi dan daftar TUK tetap dapat digunakan.</p>}
    {!entries.some(hasCoordinates) && <p className={styles.message} role="status">Belum ada lokasi peta untuk hasil ini. TUK tetap tersedia pada daftar.</p>}
  </div>;
}
